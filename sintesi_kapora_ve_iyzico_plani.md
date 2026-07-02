# Sintesi Restaurant - Chef's Table Kapora Entegrasyonu ve iyzico Kılavuzu

Bu rapor ve plan, **Sintesi Restaurant** web sitesine eklenecek olan Chef's Table rezervasyonlarında kapora/ön ödeme alma sisteminin teknik entegrasyon adımlarını, iyzico başvuru sürecini ve güncel maliyet yapılarını içermektedir.

---

## 📌 BÖLÜM 1: iyzico Süreçleri ve Gerekli Adımlar

Sistemin çalışabilmesi için bir iyzico Üye İşyeri hesabına ihtiyacınız vardır. Süreç iki aşamadan oluşur: **Sandbox (Test) Aşaması** ve **Canlı Hesap Aşaması**.

### A. Üye İşyeri Başvurusu ve Canlıya Geçiş Adımları
1. **Başvuru Yapma:** [iyzico.com](https://www.iyzico.com) adresine gidin ve **"Kurumsal Başvuru"** butonuna tıklayarak kaydolun.
2. **Belgelerin Yüklenmesi:** iyzico paneline giriş yaptıktan sonra sizden aşağıdaki şirket evrakları istenecektir:
   * Vergi Levhası
   * İmza Sirküleri (veya İmza Beyannamesi)
   * Ticari Sicil Gazetesi (varsa)
   * Şirket Yetkilisinin Kimlik Fotokopisi
   * Şirket adına açılmış banka hesabına ait IBAN belgesi (şirket unvanı ile birebir uyuşmalıdır).
3. **Web Sitesi Uyumluluk Kontrolleri (Kritik):** iyzico başvurunuzu onaylamadan önce sitenizde şu yasal ve teknik gereksinimlerin olmasını zorunlu kılar:
   * **SSL Sertifikası (HTTPS):** Siteniz mutlaka `https://` protokolü ile çalışmalıdır (Sitenizde zaten aktif durumdadır).
   * **Yasal Sözleşmeler:** Sitenin rezervasyon/ödeme aşamasında veya footer alanında **Mesafeli Satış Sözleşmesi (MSS)**, **İptal & İade Politikası** ve **KVKK Aydınlatma Metni** yer almalıdır.
   * **Şirket Bilgileri:** Web sitesinin footer veya iletişim sayfasında şirketin resmi unvanı, adresi, telefon numarası ve vergi dairesi/numarası açıkça yazmalıdır.
   * **iyzico Logoları:** Ödeme sayfasında iyzico ve kart logolarının görünür olması gereklidir (iyzico entegrasyonu tamamlandığında otomatik gelir).

### B. Sandbox (Test) Hesabı Açma (Geliştirme İçin)
Entegrasyon sürecinde sitenizi bozmadan ve gerçek para çekmeden test yapabilmemiz için:
1. [sandbox-merchant.iyzico.com](https://sandbox-merchant.iyzico.com) adresinden ücretsiz bir test hesabı oluşturulur.
2. Test paneline girdikten sonra **Ayarlar > Firma Ayarları** bölümünden **API Key** ve **Secret Key** alınır.
3. Bu anahtarlar kodda tanımlanarak iyzico'nun sunduğu test kartları (sanal kartlar) ile ödeme akışı uçtan uca test edilir.

---

## 💰 BÖLÜM 2: iyzico Maliyet Analizi ve Komisyon Yapısı

iyzico, geleneksel banka poslarına kıyasla şeffaf ve kurulum maliyeti olmayan bir yapı sunar.

| Kalem | Ücret / Oran | Açıklama |
| :--- | :--- | :--- |
| **Başlangıç/Kurulum Ücreti** | **0 TL** | Herhangi bir giriş ücreti alınmaz. |
| **Yıllık / Aylık Sabit Ücret** | **0 TL** | Ciro yapılmayan aylarda hiçbir ücret kesilmez. |
| **İşlem Başı Sabit Ücret** | **~0.25 TL - 0.79 TL** | Başarılı her işlem başına kesilen sabit tutar. |
| **Komisyon Oranı** | **%2.29 ile %3.99** | Şirket türünüze, cironuza ve sektöre göre belirlenir. (Sintesi kurumsal ciro hacmine göre iyzico temsilcisi ile özel indirimli oran görüşülebilir). |
| **İptal / İade Maliyeti** | **0 TL** | Müşterinin kaporasını iptal edip iade ettiğinizde iyzico komisyon almaz, tüm tutar müşteriye aynen iade edilir. |
| **Ödeme (Hak Ediş) Süresi** | **Haftalık / Blokeli** | Standart olarak tahsil edilen tutarlar iyzico tarafından belirlenen gün blokajından sonra (genellikle 7 gün veya her Çarşamba) doğrudan şirket banka hesabınıza aktarılır. |

---

## 🛠️ BÖLÜM 3: Teknik Entegrasyon Planı (Uçtan Uca Akış)

Ödeme akışı iyzico'nun en güvenli ve PCI-DSS uyumlu olan **Checkout Form (Ortak Ödeme Formu)** altyapısıyla kurulacaktır.

### A. Veritabanı Değişikliği
Mevcut rezervasyon yapısını bozmadan, yeni alanlar eklemek için `db_update_payment.php` dosyası oluşturulacak ve çalıştırılacaktır.
* `rezervasyonlar` tablosuna eklenecek alanlar:
  * `odeme_durumu` (varsayılan: `'bekliyor'`, değerler: `'bekliyor'`, `'odendi'`, `'iade_edildi'`)
  * `odenen_tutar` (DECIMAL(10,2))
  * `odeme_token` (VARCHAR(100) - iyzico callback eşleştirmesi için)
  * `odeme_id` (VARCHAR(100) - iyzico sistemindeki ödeme referansı)

### B. Teknik Dosya Yapısı ve Görevleri
1. **[iyzico_helper.php](file:///Users/berkayulku/Desktop/sintesi/iyzico_helper.php) (Ödeme Sınıfı - Yeni):**
   * iyzico API isteklerini (Token üretimi, SHA256 Hash imzası, Curl HTTP istekleri) yöneten bağımsız sınıf.
2. **[rezervasyon_gonder.php](file:///Users/berkayulku/Desktop/sintesi/rezervasyon_gonder.php) (İşleyici - Mevcut):**
   * Rezervasyon tipi `chefs_table` ise rezervasyonu veritabanına `odeme_bekliyor` durumunda kaydeder.
   * `iyzico_helper` ile ödeme oturumu başlatır ve dönen ödeme formunu tarayıcıya JSON yanıtı olarak iletir.
3. **[iyzico_callback.php](file:///Users/berkayulku/Desktop/sintesi/iyzico_callback.php) (Geri Dönüş Sayfası - Yeni):**
   * Ödeme tamamlandığında iyzico'nun yönlendireceği PHP sayfasıdır.
   * Ödeme sonucunu doğrular. Başarılıysa rezervasyon durumunu `beklemede` yapar ve restoran yöneticilerine rezervasyon onay e-postasını tetikler.
4. **[chefs-table-rezervasyon.html](file:///Users/berkayulku/Desktop/sintesi/chefs-table-rezervasyon.html) (Arayüz - Mevcut):**
   * Form gönderildikten sonra iyzico Checkout Formunu sayfa içinde şık bir modal pop-up olarak açar.
5. **[admin/panel.php](file:///Users/berkayulku/Desktop/sintesi/admin/panel.php) (Yönetim Paneli - Mevcut):**
   * Rezervasyon listesinde ödeme durumunu ve tutarını gösterir.
   * Rezervasyon iptal edildiğinde iyzico API'sini tetikleyerek parayı müşterinin kartına anında iade eden "Kaporayı İade Et" butonu eklenir.

---

## ❓ Karar Verilmesi Gereken Tasarım Tercihleri

Lütfen kuruluma başlamadan önce aşağıdaki tercihinizi iletin:
1. **Kapora Tutarı ve Yapısı:** Kapora ücreti **kişi başı** mı (Örn: 2 kişi için 2x500 TL = 1000 TL) yoksa rezervasyon başına **sabit bir tutar** mı (Örn: Kaç kişi olunursa olunsun masa koruma bedeli 1000 TL) olsun?
2. **Test Ortamı Tercihi:** İlk entegrasyonu test etmek için iyzico Sandbox (test hesabı) üzerinden mi ilerleyelim?
