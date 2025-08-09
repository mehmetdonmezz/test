# ARDİO – NFC/QR Tabanlı Acil Bilgi Platformu

ARDİO, Alzheimer ve zihinsel engelli bireyler için geliştirilen akıllı bileklik ile acil durumlarda kişinin kritik sağlık ve iletişim bilgilerine hızlı erişim sağlayan açık kaynak bir web uygulamasıdır.

Kayıtlı hasta/veli panelinden hasta bilgileri yönetilir; her kullanıcı için HMAC imzalı güvenli bir “Acil Profil” bağlantısı/QR üretilir. Bilekliği bulan kişi NFC/QR ile acil bilgi kartına erişerek hızlıca yakınlarına ulaşabilir.

---

## İçindekiler
- [Özellikler](#özellikler)
- [Ekran Görüntüleri](#ekran-görüntüleri)
- [Teknolojiler](#teknolojiler)
- [Kurulum](#kurulum)
  - [Gereksinimler](#gereksinimler)
  - [Projenin Çalıştırılması](#projenin-çalıştırılması)
  - [Veritabanı Şeması](#veritabanı-şeması)
  - [Yapılandırma](#yapılandırma)
- [Kullanım](#kullanım)
  - [Kullanıcı Paneli](#kullanıcı-paneli)
  - [Acil Profil ve QR](#acil-profil-ve-qr)
  - [Admin Paneli](#admin-paneli)
  - [Site Yönetimi](#site-yönetimi)
- [Güvenlik Notları](#güvenlik-notları)
- [Yol Haritası](#yol-haritası)
- [Katkıda Bulunma](#katkıda-bulunma)
- [Lisans](#lisans)

---

## Özellikler
- NFC/QR destekli güvenli acil profil sayfası (HMAC imzalı link)
- Kullanıcı panelinden hasta bilgileri yönetimi
  - Genel bilgiler, adres
  - Sağlık bilgileri (alerjiler, kronik hastalıklar, ilaçlar)
  - Doktor ve acil iletişim bilgileri
- PDF/yazdırılabilir “Acil Bilgi Kartı”
- Karanlık/aydınlık tema (kullanıcı tercihine göre)
- Admin paneli
  - Kullanıcı listesi, arama/filtreleme, sıralama, sayfalama
  - CSV dışa aktarma
  - Çoklu seçimle toplu silme/rol değiştirme
  - Başka kullanıcı olarak oturum açma (impersonate) ve geri dönme
  - Yeni kullanıcı oluşturma
- Site yönetimi (kod yazmadan)
  - Hero metinleri, iletişim e-postası ve sosyal linkler
  - Ana sayfa galeri görsellerini yükleme/silme

## Ekran Görüntüleri
> Not: Aşağıdaki görseller yer tutucudur. Projeyi yerelde çalıştırdığınızda gerçek ekran görüntülerini `docs/screenshots/` altına alıp buraya ekleyebilirsiniz.

- Ana Sayfa (Landing)
- Kullanıcı Paneli
- Acil Profil (NFC/QR ile açılan sayfa)
- Admin Paneli

## Teknolojiler
- PHP 8+ (PDO)
- MySQL/MariaDB
- Bootstrap 5.3 + Bootstrap Icons
- Plain JavaScript (tema/validation)

---

## Kurulum

### Gereksinimler
- PHP 8+ (PDO MySQL etkin)
- MySQL/MariaDB
- (Opsiyonel) GD eklentisi – avatar kırpma/yeniden boyutlandırma için
- Apache/Nginx/PHP Built-in Server

### Projenin Çalıştırılması
1. Kaynağı klonlayın:
   ```bash
   git clone https://github.com/kullanici/ardio.git
   cd ardio
   ```
2. Web sunucusunun köküne yönlendirin (örn. `http://localhost/ardio`).
3. Veritabanını oluşturun ve aşağıdaki şemayı uygulayın.
4. `config.php` üzerinden veritabanı bilgilerini (ENV veya direkt) ayarlayın.
5. Tarayıcıdan `http://localhost/ardio` adresine gidin.

### Veritabanı Şeması
```sql
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS patient_info (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  hasta_adi VARCHAR(120) NOT NULL,
  hasta_dogum DATE NOT NULL,
  hasta_kan VARCHAR(20) NULL,
  hasta_ilac TEXT NULL,
  hasta_notlar TEXT NULL,
  CONSTRAINT fk_patient_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Yapılandırma
- `config.php` ENV değişkenlerini destekler; yoksa varsayılanları kullanır:
  - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
  - `PUBLIC_LINK_SECRET` (HMAC için gizli anahtar – üretimde değiştirin)
- Site ayarları JSON dosyası: `assets/site.json`
  - Admin’den düzenlenir; hero metinleri, sosyal linkler ve galeri görselleri içerir.

> Üretimde `PUBLIC_LINK_SECRET` değerini benzersiz ve güçlü bir anahtarla değiştirin.

---

## Kullanım

### Kullanıcı Paneli
- Giriş yapıldıktan sonra `panel.php` üzerinden hasta bilgilerini üç sekmeden düzenleyebilirsiniz:
  - Genel: Ad-soyad, doğum tarihi, kan grubu, adres
  - Sağlık: Alerjiler, kronik hastalıklar, ilaçlar, doktor bilgisi
  - İletişim: Acil iletişim kişi/telefon
- Panel, otomatik QR bağlantısı ve yazdırılabilir “Acil Bilgi Kartı” linkini gösterir.

### Acil Profil ve QR
- Her kullanıcı için bağlantı: `p.php?uid={id}&code={hmac}`
- Kodlar `PUBLIC_LINK_SECRET` ile imzalanır.
- QR görseli, panelde ve acil profil sayfasında gösterilir.

### Admin Paneli
- `admin.php`: kullanıcı listesi, arama/filtre/sıralama/sayfalama, CSV dışa aktarma
- Toplu işlemler: silme, admin yap/kullanıcı yap
- “Giriş Yap” ile belirli bir kullanıcı adına geçebilirsiniz (impersonate); `admin_unimpersonate.php` ile geri dönersiniz.
- İlk admini oluşturmak için (sistemde admin yoksa):
  - Normal kullanıcıyla giriş yapıp `admin_bootstrap.php` adresini açın; mevcut kullanıcıyı admin yapar.

### Site Yönetimi
- `admin_site.php` üzerinden:
  - Ana sayfa hero başlığı/alt metni ve iletişim e-postası
  - Sosyal medya linkleri
  - Galeri görselleri (yükle/sil)
- Ana sayfa (`index.php`) bu ayarları `assets/site.json`’dan dinamik okur.

---

## Güvenlik Notları
- `PUBLIC_LINK_SECRET` üretimde env ile yönetin; kod deposuna sabitlemeyin.
- `assets/gallery/` ve `assets/avatars/` dizinleri için uygun yazma izinleri verin; sadece görsel dosyalar alın.
- Admin ve impersonate işlemlerini kısıtlı tutun; oturum sürelerini sınırlandırın.

## Yol Haritası
- [ ] Çoklu dil desteği (tr/en)
- [ ] Galeri sıralama ve kapak görseli seçimi
- [ ] PDF çıktısı için kurumsal şablon
- [ ] Rate limiting ve temel güvenlik başlıkları
- [ ] Docker-compose geliştirme ortamı

## Katkıda Bulunma
1. Bu depoyu forklayın
2. Yeni bir branch oluşturun: `feat/ozellik-adi`
3. Değişikliklerinizi yapın ve test edin
4. Pull Request açın

## Lisans
Bu proje MIT Lisansı ile lisanslanmıştır. Ayrıntılar için `LICENSE` dosyasına bakın.