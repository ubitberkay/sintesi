# Sintesi Restaurant - Uçtan Uca Web Sitesi Analiz ve Geliştirme Önerileri Raporu

Sintesi Restaurant web sitesinin mevcut durum analizi; **Güvenlik**, **SEO**, **Performans**, **Kullanıcı Deneyimi (UX/UI)** ve **Kod Mimarisi** olmak üzere 5 ana başlık altında incelenmiş, tespit edilen zayıf yönler ile eklenebilecek yeni özellikler detaylandırılmıştır.

---

## 📌 1. Genel Durum ve Mevcut Güçlü Yönler
* **Estetik Tasarım Dili:** Koyu tema tercihleri (`#0c0c0c` ve `#151515`), pas rengi/altın tonlu vurgu rengi (`#9D432C`), elegant tipografi (`Cormorant Garamond` ve `Montserrat`) restoranın premium kimliğini başarıyla yansıtmaktadır.
* **Hibrit Veritabanı Yapısı:** `config.php` içerisinde yer alan local/production tespiti ile yerelde pratik SQLite, canlı sunucuda ise kararlı MySQL kullanımı mimari açıdan geliştirici dostudur.
* **Otomasyon (Cron Reminders):** `cron_reminders.php` aracılığıyla 2 saat kala müşterilere otomatik hatırlatma maili atılması ve gelmeyen müşterilerin 1 saat sonra otomatik olarak `gelmedi` işaretlenmesi operasyonel verimlilik sağlamaktadır.
* **İki Dilli Yapı:** `translations.js` yardımıyla sitenin tamamında Türkçe ve İngilizce dil seçeneğinin sunulması uluslararası misafirler için kritiktir.

---

## ⚠️ 2. Kritik Güvenlik Analizi ve Riskler
Sitenin güvenliğiyle ilgili acilen müdahale edilmesi gereken noktalar bulunmaktadır:

### A. Sunucuda Açıkta Durma Riski Olan Kurulum Dosyaları (Kritik)
* **Tespit:** Root dizininde bulunan `kurulum.php` ve `kurulum_menu.php` dosyaları, veritabanı tablolarını sıfırlama (`DROP`/`DELETE`) ve admin kullanıcısı oluşturma yeteneklerine sahiptir.
* **Risk:** Kötü niyetli bir kullanıcı tarayıcıdan `sintesi.com.tr/kurulum.php` veya `kurulum_menu.php` adresine giderek **tüm veritabanı verilerini (rezervasyonları, menüleri) silebilir** ve admin şifresini sıfırlayabilir.
* **Çözüm:** Bu dosyalar kuruluma özel çalıştırıldıktan sonra **sunucudan tamamen silinmelidir** veya `.htaccess` ile dışarıdan erişime kapatılmalıdır.
* **Durum:** **[Uygulandı]** `kurulum.php` dosyası `.gitignore` listesine eklenerek sürüm kontrolünün dışına çıkarıldı. Bundan sonraki tüm DB güncelleme işlemleri `kurulum_db.php` gibi yeni isimdeki bağımsız dosyalar üzerinden yapılacaktır.

### B. Varsayılan Admin Şifresi
* **Tespit:** `kurulum.php` içerisinde varsayılan şifre `Sintesi2026!` ve kullanıcı adı `admin` olarak hardcoded (sabit kodlanmış) durumdadır.
* **Risk:** Eğer kurulum sonrası admin panelinden bu şifre güncellenmediyse, brute-force veya basit bir deneme ile yönetim paneli ele geçirilebilir.
* **Çözüm:** Admin panelinde ilk girişte zorunlu şifre değiştirme adımı eklenmeli veya kurulum scriptinde rastgele şifre üretilip ekranda yazdırılmalıdır.

### C. Konfigürasyon ve Kimlik Bilgilerinin Güvenliği
* **Tespit:** Database ve SMTP şifreleri (`SMTP_PASS`, `DB_PASS`) `config.php` içerisinde doğrudan düz metin (plaintext) olarak yazılmıştır.
* **Risk:** Kod tabanı yanlışlıkla açık kaynaklı bir repo'ya pushlanırsa veya sunucuda bir dizin listeleme açığı oluşursa tüm kimlik bilgileri ifşa olur.
* **Çözüm:** Credentials (şifreler) için `.env` dosyası (veya web erişimi olmayan bir üst dizinde ayrı bir config dosyası) kullanılmalı ve `config.php` içerisine `getenv()` ile çekilmelidir.

### D. Oturum (Session) ve Çerez Güvenliği
* **Tespit:** `config.php` üzerinde `session_start()` çağrısı yapılmış ancak oturum çerezleri için güvenlik parametreleri tanımlanmamıştır.
* **Risk:** Session Hijacking (Oturum Çalma) saldırılarına zemin hazırlar.
* **Çözüm:** `session_start()` çağrısından önce aşağıdaki güvenlik parametreleri eklenmelidir:
  ```php
  session_start([
      'cookie_lifetime' => 86400,
      'cookie_secure' => true, // Yalnızca HTTPS üzerinde iletilsin
      'cookie_httponly' => true, // JS ile çerez okunamasın (XSS koruması)
      'cookie_samesite' => 'Lax' // CSRF koruması
  ]);
  ```
  Ayrıca, başarılı admin girişinde `admin/index.php` içinde `session_regenerate_id(true);` fonksiyonu çağrılarak oturum sabitleme (Session Fixation) engellenmelidir.

---

## 🔍 3. SEO ve Arama Motoru İndekslenebilirliği
Arama motorlarında (Google) restoranın üst sıralarda çıkması için şu iyileştirmeler yapılmalıdır:

### A. JavaScript Tabanlı Çeviri (Client-Side Translation) Limiti
* **Tespit:** Dil geçişleri `translations.js` içindeki sözlük kullanılarak tarayıcıda (Client-Side) JavaScript ile yapılmaktadır.
* **Risk:** Googlebot ve diğer arama motoru örümcekleri JavaScript çalıştırabilse de, çoğunlukla sayfayı ilk yüklendiği ham HTML haliyle (yani sadece Türkçe ya da varsayılan şablon metinleriyle) indeksler. Bu durum, sitenin İngilizce içeriklerinin Google indeksine hiç girmemesine veya hatalı indekslenmesine sebep olur.
* **Çözüm:**
  1. Sayfalar PHP uzantılı hale getirilip dil kontrolü sunucu tarafında yapılabilir (`index.php?lang=en` veya url yapısı `/en/menu`).
  2. Veya en azından SEO kritik etiketleri (`<title>`, `<meta name="description">`) her dil için statik olarak ayrılmış alt klasörlerde veya PHP ile sunucu tarafında basılmalıdır.

### B. Restoran Yapılandırılmış Verisi (JSON-LD Schema) Eksikliği
* **Tespit:** Sitede Google'ın restoranları tanıması için kullandığı "Structured Data" (Yapılandırılmış Veri) bulunmamaktadır.
* **Çözüm:** Ana sayfaya (`index.html`) aşağıdaki gibi bir JSON-LD şeması eklenmelidir. Bu şema, Google arama sonuçlarında çalışma saatlerinin, adresin ve iletişim bilgilerinin doğrudan zengin içerik olarak görünmesini sağlar:
  ```html
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Restaurant",
    "name": "Sintesi Restaurant",
    "image": "https://sintesi.com.tr/sintesi.webp",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Atatürk Mahallesi Ertuğrul Gazi Sokak Metropol İstanbul Alışveriş Merkezi B2 Katı",
      "addressLocality": "Ataşehir",
      "addressRegion": "İstanbul",
      "addressCountry": "TR"
    },
    "geo": {
      "@type": "GeoCoordinates",
      "latitude": 40.994778,
      "longitude": 29.121453
    },
    "telephone": "+905326993282",
    "servesCuisine": "Modern Mutfak, Anadolu, Akdeniz, İtalyan, Fransız",
    "openingHoursSpecification": [
      {
        "@type": "OpeningHoursSpecification",
        "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
        "opens": "15:00",
        "closes": "00:00"
      }
    ],
    "menu": "https://sintesi.com.tr/menu"
  }
  </script>
  ```
* **Durum:** **[Uygulandı]** Belirtilen JSON-LD şeması `index.html` sayfasının `<head>` etiketleri arasına başarıyla eklendi.

### C. Robots.txt ve Sitemap Eksikliği
* **Tespit:** Proje kök dizininde arama motorlarına rehberlik edecek `robots.txt` ve `sitemap.xml` dosyaları bulunmamaktadır.
* **Çözüm:** Bu dosyalar üretilmeli ve Google Search Console'a kaydedilmelidir.

---

## ⚡ 4. Performans ve Hız Optimizasyonu
Sitenin ilk açılış hızı, özellikle mobil cihazlarda kullanıcıyı tutmak adına kritiktir.

### A. Çok Büyük Boyutlu Görseller
* **Tespit 1:** `/assets/aiSintesi.png` arka plan görseli **1.97 MB** boyutundadır. Arka planda duran ve bulanıklaştırılan (blur) bir görsel için bu boyut aşırıdır. Sitenin ilk yüklenmesini çok ciddi yavaşlatır.
  * *Çözüm:* Görsel `.webp` formatına dönüştürülmeli ve sıkıştırılmalıdır. Boyutu ~150 KB civarına düşürülebilir.
* **Tespit 2:** `/assets/optimized` klasöründe yer alan bazı WebP görselleri dahi çok büyüktür. Örneğin `galeri12.webp` **1.02 MB**, `galeri7.webp` **1.31 MB**, `ersin.webp` ise **795 KB** boyutundadır.
  * *Çözüm:* WebP sıkıştırma oranı artırılmalı ve görseller maksimum 1920px genişliğe çekilerek 150-250 KB altına indirilmelidir.

### B. CDN ve Caching
* **Tespit:** Flatpickr gibi CSS/JS kütüphaneleri dış CDN'den çekilmektedir, bu iyi bir pratik ancak tarayıcı önbellekleme kuralları `.htaccess` dosyasında eksiktir.
* **Çözüm:** `.htaccess` dosyasına statik dosyaların (görseller, yazı tipleri, CSS/JS) önbellekte saklanması için `Expires` başlıkları eklenmelidir:
  ```apache
  <IfModule mod_expires.c>
      ExpiresActive On
      ExpiresByType image/jpg "access plus 1 year"
      ExpiresByType image/jpeg "access plus 1 year"
      ExpiresByType image/gif "access plus 1 year"
      ExpiresByType image/png "access plus 1 year"
      ExpiresByType image/webp "access plus 1 year"
      ExpiresByType text/css "access plus 1 month"
      ExpiresByType application/pdf "access plus 1 month"
      ExpiresByType text/javascript "access plus 1 month"
      ExpiresByType application/javascript "access plus 1 month"
  </IfModule>
  ```

---

## 📱 5. Kullanıcı Deneyimi (UX/UI) Geliştirme Önerileri

### A. Menü Sayfalarının Birleştirilmesi (SPA Mantığı)
* **Mevcut Durum:** Kullanıcı `menu.html` sayfasına girdikten sonra Yemek, Alkol veya İçecek kartlarına tıklıyor; her seferinde yeni bir `.html` sayfasına (`yemekler.html`, `alkol.html`, vb.) yönlendiriliyor ve uzun loader (yükleme) animasyonlarını bekliyor.
* **Öneri:** Bu 3 sayfa yerine tek bir dinamik `menu.html` olmalı. Sayfanın en üstünde modern sekmeler (Tabs: "Yemekler", "İçecekler", "Alkol & Kokteyller") olmalı. Kullanıcı sekmeler arasında geçiş yaparken sayfa yenilenmeden veriler anında değişmeli. Bu hem gezinme hızını artırır hem de akıcı bir deneyim sunar.

### B. Rezervasyonda Saat/Kapasite Doluluğunun Canlı Gösterilmesi
* **Mevcut Durum:** Kullanıcı rezervasyon formunda tarihi seçiyor, saati seçiyor, formu doldurup "Gönder" butonuna bastıktan sonra eğer o saat doluysa "Üzgünüz bu saat doludur" uyarısı alıyor.
* **Öneri:** Flatpickr üzerinde tarih seçildikten hemen sonra arka planda `public_api.php?action=check_availability` sorgusu çalıştırılıp, tamamen dolmuş olan saat butonları **daha seçim aşamasındayken pasif (disabled)** hale getirilmelidir. Böylece kullanıcı boşuna form doldurmak zorunda kalmaz.

### C. Telefon Numarası Formatlama ve Doğrulama
* **Tespit:** `telefon` input'u sadece rakamları kabul ediyor ancak formatlama yapmıyor.
* **Öneri:** Kullanıcı yazarken otomatik olarak `(0532) 123 45 67` gibi formatlayan bir maskeleme kütüphanesi (örn: Cleave.js veya basit bir regEx input mask) eklenmesi formu daha profesyonel gösterir.
* **Durum:** **[Uygulandı]** Tüm telefon girdisi olan form sayfalarına (`index.html`, `rezervasyon.html`, `chefs-table-rezervasyon.html`, `catering.html`) özel JavaScript telefon maskeleme fonksiyonu (`setupPhoneMask`) entegre edildi. Telefon numaraları otomatik olarak `05XX XXX XX XX` biçiminde formatlanır, `5` ile başlayan girdilerin başına otomatik `0` eklenir ve `+` ile başlayan uluslararası numaralar da desteklenir.

### D. Site Konseptine Uygun Özel Form Doğrulama Arayüzü
* **Öneri:** Form gönderimlerinde standart tarayıcı hata pencereleri (tooltips) yerine sitenin genel tasarım dili ve renk paletiyle uyumlu, dinamik ve şık kırmızı hata mesajları tasarlanmalıdır.
* **Durum:** **[Uygulandı]** Tüm aktif formlara (`index.html`, `rezervasyon.html`, `chefs-table-rezervasyon.html`, `catering.html`) site tasarımıyla bütünleşik yeni hata mesajı tasarımı uygulandı. Hatalı girdilerde alanı vurgulayan kırmızı kenarlıklar, sallanma (shake) animasyonu ve dil duyarlı (Türkçe/İngilizce) açıklamalar dinamik olarak gösterilmektedir.

---

## ⚙️ 6. Yönetim Paneli (Admin Panel) Önerileri

### A. Rezervasyon Durumu Değişikliklerinde E-posta Tetikleme
* **Mevcut Durum:** Admin rezervasyonu `onaylandi` veya `iptal` durumuna aldığında veritabanı güncelleniyor ancak müşteriye otomatik onay/iptal e-postası gitmiyor (sadece ilk talepte ve hatırlatmada mail gidiyor).
* **Öneri:** Yönetici panelinden onay butonuna basıldığı an müşteriye şık tasarımlı bir "Rezervasyonunuz Onaylandı 🍽️" e-postası gitmelidir. Bu e-postanın içerisinde rezervasyon iptal linki de bulunmalıdır.

### B. İstatistik Grafikleri
* **Öneri:** Yönetici panelinin girişinde (Dashboard) aylık rezervasyon sayıları, en çok tercih edilen rezervasyon saatleri, haftanın en yoğun günleri gibi istatistikleri görselleştiren basit grafikler (Chart.js kütüphanesi ile) yer almalıdır. Bu, restoran yönetimine stratejik kararlar alırken yardımcı olur.

---

## 🚀 7. Yeni Özellik Önerileri (Yol Haritası)

1. **SMS Bildirim Entegrasyonu:**
   * E-posta bildirimlerinin yanı sıra (özellikle Türkiye'deki müşteriler e-postalarını sık kontrol etmediği için) rezervasyon oluşturulduğunda ve onaylandığında otomatik SMS (Netgsm, Mutlucell vb. API entegrasyonu ile) gönderilmesi.
2. **Chef's Table için Ön Ödeme / Kapora Sistemi:**
   * Chef's Table konsepti en fazla 8 kişilik çok butik ve maliyetli bir organizasyondur. Son dakika iptalleri (No-Show) restorana büyük zarar verir.
   * *Öneri:* Chef's Table rezervasyonlarında garanti amaçlı kredi kartı bilgilerini alma veya sembolik bir kapora/ön ödeme (iyzico vb. entegrasyonu ile) talep etme özelliği.
3. **Masa Seçim Modülü (Floor Plan):**
   * Müşterinin rezervasyon yaparken restoranın şemasını (iç mekan, dış mekan bar önü, şömine yanı vb.) görerek uygun masayı harita üzerinden seçebilmesi.
4. **Google Reviews Entegrasyonu:**
   * Müşterilerin Google üzerindeki 5 yıldızlı yorumlarını otomatik çekip ana sayfada şık bir slider ile gösteren dinamik bir bileşen.
