<?php

/**
 * Talks to the GitHub Actions API to build the Android WebView template
 * (stored in /android-template + .github/workflows/build-app.yml of this
 * same repository) into a signed APK/AAB for a given app configuration.
 */
final class GitHubBuildService
{
    private string $token;
    private string $owner;
    private string $repo;
    private string $workflowFile;
    private string $branch;

    public function __construct()
    {
        $this->token = (string) Config::get('GITHUB_TOKEN');
        $this->owner = (string) Config::get('GITHUB_OWNER');
        $this->repo = (string) Config::get('GITHUB_REPO');
        $this->workflowFile = (string) Config::get('GITHUB_WORKFLOW_FILE', 'build-app.yml');
        $this->branch = (string) Config::get('GITHUB_BRANCH', 'main');
    }

    public function isConfigured(): bool
    {
        return $this->token !== '' && $this->owner !== '' && $this->repo !== '';
    }

    /**
     * Dispatches a new build for the given app and records it in the builds table.
     */
    public function triggerBuild(array $app, bool $isNewVersion): array
    {
        if ($isNewVersion) {
            $app = AppProject::bumpVersion((int) $app['id']);
        } else {
            AppProject::markStatus((int) $app['id'], 'queued');
        }

        $buildToken = bin2hex(random_bytes(8));

        $iconUrl = $app['icon_path']
            ? rtrim((string) Config::get('APP_URL'), '/') . '/uploads/icons/' . $app['icon_path']
            : '';

        $inputs = [
            'build_token' => $buildToken,
            'app_name' => $app['name'],
            'package_id' => $app['package_id'],
            'target_url' => $app['target_url'],
            'icon_url' => $iconUrl,
            'header_color' => $app['header_color'],
            'splash_bg_color' => $app['splash_bg_color'],
            'splash_text_color' => $app['splash_text_color'],
            'splash_text' => $app['splash_text'],
            'font_name' => $app['font_name'],
            'version_code' => (string) $app['version_code'],
            'version_name' => $app['version_name'],
            'key_alias' => $app['key_alias'],
            'key_password' => $app['key_password'],
            'store_password' => $app['store_password'],
            'keystore_base64' => $app['keystore_base64'] ?? '',
        ];

        $this->request('POST', "/repos/{$this->owner}/{$this->repo}/actions/workflows/{$this->workflowFile}/dispatches", [
            'ref' => $this->branch,
            'inputs' => $inputs,
        ]);

        return Build::create((int) $app['id'], $buildToken, (int) $app['version_code'], $app['version_name']);
    }

    /**
     * Polls GitHub for the current state of a build and syncs local storage/DB
     * once the workflow run has completed. Safe to call repeatedly.
     */
    public function refresh(array $build, array $app): array
    {
        if (empty($build['github_run_id'])) {
            $runId = $this->findRunByBuildToken($build['build_token']);
            if ($runId !== null) {
                Build::setRunId((int) $build['id'], $runId);
                AppProject::markStatus((int) $app['id'], 'building');
                $build = Build::find((int) $build['id']);
            }

            return $build;
        }

        $run = $this->request('GET', "/repos/{$this->owner}/{$this->repo}/actions/runs/{$build['github_run_id']}");

        if (($run['status'] ?? '') !== 'completed') {
            return $build;
        }

        if (($run['conclusion'] ?? '') !== 'success') {
            Build::markFailed((int) $build['id'], $run['html_url'] ?? null);
            AppProject::markStatus((int) $app['id'], 'failed');
            return Build::find((int) $build['id']);
        }

        $this->importArtifacts((int) $build['id'], (int) $app['id'], (int) $build['github_run_id'], (int) $build['version_code']);

        return Build::find((int) $build['id']);
    }

    private function findRunByBuildToken(string $buildToken): ?int
    {
        // per_page=100 (the API max) gives headroom to still find this run even if
        // many other apps' builds were dispatched in the same few seconds.
        $runs = $this->request('GET', "/repos/{$this->owner}/{$this->repo}/actions/workflows/{$this->workflowFile}/runs?per_page=100&event=workflow_dispatch");

        foreach ($runs['workflow_runs'] ?? [] as $run) {
            if (str_contains((string) ($run['display_title'] ?? $run['name'] ?? ''), $buildToken)) {
                return (int) $run['id'];
            }
        }

        return null;
    }

    private function importArtifacts(int $buildId, int $appId, int $runId, int $versionCode): void
    {
        $artifacts = $this->request('GET', "/repos/{$this->owner}/{$this->repo}/actions/runs/{$runId}/artifacts");

        $targetDir = BASE_PATH . "/storage/builds/{$appId}/{$versionCode}";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $apkPath = null;
        $aabPath = null;

        foreach ($artifacts['artifacts'] ?? [] as $artifact) {
            $name = $artifact['name'];
            $zipBytes = $this->downloadArtifactZip((int) $artifact['id']);
            if ($zipBytes === null) {
                continue;
            }

            $tmpZip = tempnam(sys_get_temp_dir(), 'gha_artifact_');
            file_put_contents($tmpZip, $zipBytes);

            $zip = new ZipArchive();
            if ($zip->open($tmpZip) === true) {
                if ($name === 'release-apk') {
                    $zip->extractTo($targetDir);
                    $apkPath = $this->firstFileWithExtension($targetDir, 'apk');
                } elseif ($name === 'release-aab') {
                    $zip->extractTo($targetDir);
                    $aabPath = $this->firstFileWithExtension($targetDir, 'aab');
                } elseif ($name === 'release-keystore') {
                    $keystoreDir = sys_get_temp_dir() . '/keystore_' . $buildId;
                    $zip->extractTo($keystoreDir);
                    $jks = $this->firstFileWithExtension($keystoreDir, 'jks');
                    if ($jks !== null) {
                        $app = AppProject::find($appId);
                        if (empty($app['keystore_base64'])) {
                            AppProject::saveKeystore($appId, base64_encode((string) file_get_contents($jks)));
                        }
                    }
                }
                $zip->close();
            }

            unlink($tmpZip);
        }

        if ($apkPath !== null && $aabPath !== null) {
            Build::markSuccess($buildId, $this->relativePath($apkPath), $this->relativePath($aabPath));
            AppProject::markStatus($appId, 'ready');
        } else {
            Build::markFailed($buildId, null);
            AppProject::markStatus($appId, 'failed');
        }
    }

    private function firstFileWithExtension(string $dir, string $extension): ?string
    {
        foreach (glob($dir . '/*.' . $extension) ?: [] as $file) {
            return $file;
        }

        foreach (glob($dir . '/**/*.' . $extension) ?: [] as $file) {
            return $file;
        }

        return null;
    }

    private function relativePath(string $absolutePath): string
    {
        return ltrim(str_replace(BASE_PATH . '/storage/builds', '', $absolutePath), '/');
    }

    private function downloadArtifactZip(int $artifactId): ?string
    {
        $url = "https://api.github.com/repos/{$this->owner}/{$this->repo}/actions/artifacts/{$artifactId}/zip";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Accept: application/vnd.github+json',
                'User-Agent: WebViewAppBuilder',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($status >= 200 && $status < 300 && $body !== false) ? $body : null;
    }

    private function request(string $method, string $path, ?array $jsonBody = null): array
    {
        $ch = curl_init('https://api.github.com' . $path);
        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Accept: application/vnd.github+json',
            'X-GitHub-Api-Version: 2022-11-28',
            'User-Agent: WebViewAppBuilder',
        ];

        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ];

        if ($jsonBody !== null) {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
            $options[CURLOPT_POSTFIELDS] = json_encode($jsonBody);
        }

        curl_setopt_array($ch, $options);
        $body = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode((string) $body, true);
        return is_array($decoded) ? $decoded : [];
    }
}
