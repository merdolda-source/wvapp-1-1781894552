# WebView App Builder

Kullanıcıların kendi web sitelerini, klasik PHP tabanlı bir web panel üzerinden
imzalı Android **APK** ve **AAB** (Play Store) paketlerine dönüştürmesini
sağlayan ücretsiz bir platform. Derleme işini gerçekten yapan taraf bu reponun
içindeki GitHub Actions workflow'udur; PHP tarafı sadece derlemeyi tetikler,
durumunu takip eder ve bitince dosyaları kullanıcının indirme alanına indirir.

## Özellikler

- E-posta/şifre ile **ve** Google ile kayıt/giriş
- Kullanıcı başına en fazla **5 uygulama** (`.env` içinden değiştirilebilir)
- Her uygulama için: isim, paket adı (otomatik), ikon, hedef web adresi,
  üst bar rengi, açılış (splash) ekranı arka plan/metin/yazı rengi, yazı fontu
- "Derle" butonu → GitHub Actions → imzalı APK + AAB → uygulamanın indirme alanı
- Her uygulama için **tek bir imzalama anahtarı** ilk derlemede üretilir ve
  sonraki tüm sürümlerde otomatik olarak yeniden kullanılır (Play Store
  güncellemeleri için zorunludur — imzalama anahtarı değişirse güncelleme
  yayınlanamaz)

## Klasör Yapısı

```
public/                 Web kökü (buraya document root ayarlanmalı)
src/                     PHP uygulama kodu (Auth, Models, GitHubBuildService...)
templates/               Görünüm dosyaları
database/schema.sql      MySQL şeması
android-template/        Derlenecek Android (Kotlin) WebView proje şablonu
.github/workflows/       GitHub Actions derleme iş akışı (build-app.yml)
storage/builds/          Üretilen APK/AAB dosyaları (dışarıya kapalı, PHP üzerinden indirilir)
```

## Kurulum

### 1. Gereksinimler

- PHP 8.1+ (`curl`, `pdo_mysql`, `zip` eklentileri açık olmalı)
- MySQL 5.7+/MariaDB
- Apache + `mod_rewrite` (Nginx kullanıyorsanız eşdeğer rewrite kuralını siz eklemelisiniz)
- **Bu GitHub reposunun kendisi** (derleme workflow'u burada çalışır)

### 2. Veritabanı

```
mysql -u kullanici -p veritabani_adi < database/schema.sql
```

### 3. Ortam değişkenleri

`.env.example` dosyasını `.env` olarak kopyalayın ve doldurun:

```
cp .env.example .env
```

- `DB_*` → MySQL bağlantı bilgileriniz
- `APP_URL` → sitenizin herkese açık adresi (ikon dosyalarının GitHub Actions
  tarafından indirilebilmesi için **herkese açık** olmalıdır)
- `APP_MAX_APPS_PER_USER` → kullanıcı başına izin verilen uygulama sayısı (varsayılan 5)

### 4. GitHub bağlantısı (derleme için zorunlu)

1. Bu depoyu **private** yapın (workflow, imzalama anahtarını geçici bir
   artifact olarak yüklüyor; repo herkese açıksa bu anahtarı herkes indirebilir).
2. GitHub → Settings → Developer settings → Personal access tokens → Fine-grained
   token oluşturun; bu depoya `Actions: Read and write`, `Contents: Read` izni verin
   (klasik token kullanıyorsanız `repo` + `workflow` scope yeterlidir).
3. `.env` içine yazın:
   - `GITHUB_TOKEN` → oluşturduğunuz token
   - `GITHUB_OWNER` / `GITHUB_REPO` → bu deponun sahibi/adı
   - `GITHUB_WORKFLOW_FILE=build-app.yml`
   - `GITHUB_BRANCH=main`

### 5. Google ile giriş (opsiyonel ama istendi)

1. [Google Cloud Console](https://console.cloud.google.com/) → APIs & Services →
   Credentials → "OAuth client ID" → Web application.
2. Authorized redirect URI: `https://siteniz.com/auth/google/callback`
3. `.env` içine `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` yazın.
4. Bu alanlar boş bırakılırsa "Google ile Giriş" butonu otomatik olarak gizlenir,
   klasik e-posta/şifre kaydı yine çalışır.

### 6. Web sunucusu

Document root'u **`public/`** klasörüne ayarlayın. `public/.htaccess` dosyası
statik dosyaları (yüklenen ikonlar, CSS/JS) doğrudan sunar, geri kalan her şeyi
`index.php` üzerinden yönlendirir.

## Derleme Akışı Nasıl Çalışır?

1. Kullanıcı panelden uygulamasını oluşturur/düzenler ve "Derle" butonuna basar.
2. PHP, `.github/workflows/build-app.yml` dosyasını GitHub REST API üzerinden
   (`workflow_dispatch`) tetikler ve benzersiz bir `build_token` gönderir.
3. Workflow, `android-template/` klasöründeki Kotlin projesini kullanıcının
   ayarlarıyla (isim, ikon, renkler, font, hedef adres, sürüm) derler, imzalar
   ve APK+AAB'yi GitHub Actions "artifact" olarak yükler.
4. Uygulama detay sayfası birkaç saniyede bir `/apps/{id}/status` uç noktasını
   çağırır; bu uç nokta GitHub API'den çalışmanın durumunu sorar, tamamlandığında
   artifact'leri indirip `storage/builds/` altına kaydeder.
5. Kullanıcı, uygulamanın indirme alanından imzalı APK ve AAB dosyalarını indirir.

İlk derlemede workflow yeni bir imzalama anahtarı (`keystore`) üretir ve bunu da
geçici bir artifact olarak yükler; PHP bunu bir kere indirip veritabanında saklar
ve sonraki her "yeni sürüm derle" isteğinde aynı anahtarı workflow'a geri gönderir.
**Bu anahtarı asla kaybetmeyin** — kaybolursa o uygulamanın güncellemesi Play
Store'a yüklenemez, yalnızca tamamen yeni bir uygulama olarak yayınlanabilir.

## Yazı Fontları

`android-template/app/src/main/res/font/` altında Google Fonts'tan (OFL lisanslı)
hazır getirilmiş birkaç font bulunur: Open Sans, Montserrat, Poppins, Lobster,
Playfair Display, Nunito. Kullanıcı panelde birini seçtiğinde uygulama açılış
ekranında o font kullanılır; "Sistem Varsayılanı" seçilirse cihazın Roboto fontu
kullanılır. Yeni bir font eklemek için `.ttf` dosyasını bu klasöre atıp
`src/Support/Fonts.php` içine bir satır eklemeniz yeterlidir.

## Sınırlamalar / Notlar

- İkon yüklenmezse şablondaki mavi placeholder ikon kullanılır.
- Hedef site `http://` de olabilir (cleartext trafiğe izin verilir), ancak
  `https://` önerilir.
- Bir GitHub Actions çalışması birkaç dakika sürebilir; ücretsiz GitHub
  Actions dakika kotanız bulunmalıdır (public repo'larda sınırsız, private
  repo'larda aylık ücretsiz kota vardır).
