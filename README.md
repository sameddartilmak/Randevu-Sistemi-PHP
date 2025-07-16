# Hasta Randevu Sistemi - README

Bu proje, bir hastane için geliştirilen online randevu alma sistemidir. Kullanıcılar (hastalar), sisteme kayıt olarak doktorlardan randevu talebinde bulunabilir. Sistem admin, doktor ve hasta panellerinden oluşmaktadır.

## Hazırlayanlar 
- Samed DARTILMAK

## Gereksinimler

- PHP 7.4 veya üzeri
- MySQL/MariaDB
- Apache veya Nginx
- Composer (isteğe bağlı)
- Tarayıcı (Chrome, Firefox, vs.)

## Kurulum Adımları

1. **Projeyi İndirin veya Kopyalayın**


2. **Veritabanı Ayarları**
- MySQL'de yeni bir veritabanı oluşturun. Örnek:
  ```sql
  CREATE DATABASE randevu_sistemi;
  ```
- `db.php` dosyasını açın ve aşağıdaki bilgileri kendinize göre düzenleyin:
  ```php
  $pdo = new PDO("mysql:host=localhost;dbname=randevu_sistemi;charset=utf8", "kullanici_adi", "parola");
  ```

3. **Veritabanı Tablolarını Oluşturun**
- `database.sql` adında bir SQL dosyanız varsa phpMyAdmin veya terminal ile içeriğini çalıştırın.
- Aksi durumda, `users`, `doctors`, `patients`, `appointments` gibi tabloları manuel oluşturmanız gerekir. (Yardım istersen örnek şema sağlayabilirim.)

4. **Klasör ve Dosya Yapısını Kontrol Edin**
- Ana dizin içinde şunlar olmalı:
  ```
  add_user.php
  appointment_action.php
  cancel_appointment.php
  error.php
  delete_user.php
  logout.php
  make_appointment.php
  process_login.php
  index.php
  login.php
  register_patient.php
  dashboard_admin.php
  dashboard_doctor.php
  dashboard_patient.php
  process_register.php
  remember_me_check.php
  db.php
  ```
- Ayrıca `assets/`, `css/` gibi klasörler varsa doğru şekilde yüklendiğinden emin olun.

5. **Sunucuda Çalıştırın**
- Proje klasörünü `htdocs/` (XAMPP için) veya sunucunuzun web kök dizinine yerleştirin.
- Tarayıcıda aşağıdaki gibi açın:
  ```
  http://localhost/randevu_sistemi/index.php
  ```

## Kullanıcı Rolleri ve Giriş

- **Hasta (patient):** Kayıt olup, randevu alabilir.
- **Doktor (doctor):** Kendi panelinden gelen randevuları onaylayabilir.
- **Admin (admin):** Doktorları ve genel yapıyı yönetebilir.

> **Not:** Giriş yaptıktan sonra rolünüze göre otomatik olarak ilgili panele yönlendirilirsiniz.

## Güvenlik Özellikleri

- Şifreler `password_hash()` ile güvenli şekilde saklanır.
- CSRF koruması mevcuttur.
- "Beni Hatırla" (Remember Me) özelliği güvenli token ile sağlanmıştır.

## Destek ve Geliştirme

Bu proje eğitim amaçlı geliştirilmiştir. Geliştirmeye açık olup, yeni özellikler kolaylıkla entegre edilebilir. Proje yapılırken açık kaynaklı kodlardan ve yapay zekadan yardım alınmıştır.

