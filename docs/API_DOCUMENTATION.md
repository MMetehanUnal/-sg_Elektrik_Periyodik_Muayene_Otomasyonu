# İSG Elektrik Periyodik Muayene Otomasyonu - API Dokümantasyonu

Bu doküman, Android uygulamasının arka uç sistemiyle iletişim kurmasını sağlayan REST API'nin detaylarını içerir.

## 1. Genel Bilgiler

- **Base URL:** `http://{sunucu_adresi}/api/`
- **İstek/Yanıt Formatı:** `application/json`
- **Kimlik Doğrulama:** `Bearer Token` (JWT)
- **Kurum Kapsamı (Scoping):** Kuruma özgü verileri okumak/yazmak için isteklerde `X-Institution-Id` header'ı gönderilmelidir.

### Standart Başarılı Yanıt
```json
{
  "success": true,
  "message": "İşlem başarılı",
  "data": { ... }
}
```

### Standart Hata Yanıtı
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Geçersiz parametre."
  }
}
```

---

## 2. Kimlik Doğrulama

### Giriş Yap
**`POST /auth/login.php`**
Kullanıcı adı ve şifre ile giriş yapıp token alır.
- **Auth:** Gerekmez.
- **Request Body:**
```json
{
  "username": "admin",
  "password": "password123"
}
```
- **Response:**
```json
{
  "success": true,
  "message": "Giriş başarılı.",
  "data": {
    "token": "eyJhbG...",
    "token_type": "Bearer",
    "expires_in": 86400,
    "user": {
      "id": 1,
      "username": "admin",
      "role": "admin"
    }
  }
}
```

### Profil Bilgileri
**`GET /auth/me.php`**
Geçerli kullanıcının profil bilgilerini getirir.
- **Auth:** Bearer Token

---

## 3. Yönetim Endpoint'leri

### Kurumlar
**`api/kurumlar.php`**
Kullanıcıya ait kurumları yönetir. (X-Institution-Id gerekmez)
- **GET:** Tüm kurumları listeler.
- **GET `?id=X`:** Kurum detayı getirir.
- **POST:** Kurum ekler. (Zorunlu alanlar: `firma_adi`, `adresi`, `il_kodu`, `kurum_kodu`)
- **PUT `?id=X`:** Kurumu günceller.
- **DELETE `?id=X`:** Kurumu siler.

### Tesis Seçimi
**`POST api/tesis-secimi.php`**
Kullanıcının aktif çalışacağı kurumu seçtiğini doğrular. Android uygulaması, başarılı yanıt aldıktan sonra `kurum_id`'yi yerel deposuna (DataStore vb.) kaydetmeli ve sonraki isteklerde `X-Institution-Id` header'ı olarak göndermelidir.
- **Body:** `{"kurum_id": 5}`

### Yetkili Kişiler
**`api/yetkili-kisiler.php`**
- Global (Tüm kullanıcılar için ortak)
- Standart CRUD (GET, POST, PUT, DELETE)

### Cihazlar
**`api/cihazlar.php`**
Kullanıcıya özel ölçüm cihazları listesi.
- Standart CRUD (GET, POST, PUT, DELETE)

---

## 4. Raporlama ve Dashboard

### Dashboard
**`GET api/dashboard.php`**
Özet istatistikleri döndürür. (Toplam kurum, toplam rapor sayıları vb.) `X-Institution-Id` header'ı verilirse o kuruma ait sayıları getirir.

### Genel Raporlar
**`GET api/raporlar.php`**
Kuruma ait (topraklama, iç tesisat, yıldırımdan korunma, yangın) tüm rapor tiplerini birleşik bir liste (UNION) olarak döndürür.
- **Header:** `X-Institution-Id: X`

---

## 5. Kontrol Modülleri

Kontrol modülleri için genel CRUD mantığı:
- Rapor tablolarına (`grounding_reports`, `internal_installation_reports` vb.) kayıt atılırken formdan gelen tüm veriler JSON body içinde gönderilir.
- Form alanları için veritabanı şemasındaki alan adları kullanılır.

### Topraklama Ölçümü
- **Rapor CRUD:** `api/kontrol/topraklama.php`
- **Ölçümler (Satırlar):** `POST api/kontrol/topraklama-olcumler.php?section=5_1`
  - Body: `{"report_id": 1, "rows": [...]}`
  - Not: Bu endpoint DELETE + reinsert mantığıyla çalışır. Her kayıtta tüm tabloyu temizleyip yeni gelenleri yazar.

### İç Tesisat Panoları
**`POST api/kontrol/ic-tesisat-panolar.php`**
En karmaşık yapı bu endpoint'tedir. `action` parametresi kullanılarak çağrılır.
- `action=add_panel`: Yeni pano ekler.
- `action=delete_panel`: Pano siler.
- `action=save_section5`: Gözle muayene sorularını kaydeder.
- `action=save_section6_1`: Aşırı akım/linye satırlarını kaydeder.
- `action=save_section6_2`: Potansiyel dengeleme.
- `action=save_section6_3`: Halı yalıtkanlık direnci.

### Yıldırımdan Korunma
- **Rapor CRUD:** `api/kontrol/yildirimdan-korunma.php`
- Check-list (Section 4) soruları, `POST` ve `PUT` işlemlerinde `section4` dizisi altında gönderilebilir.

### Yangın Algılama Sistemi
- **Rapor CRUD:** `api/kontrol/yangin-algilama.php`
- **Denetim Sonuçları ve Loop'lar:** `POST api/kontrol/yangin-algilama.php?action=save_inspection`
  - Body: `{"report_id": 1, "inspection_results": {...}, "loops": [...]}`

---

## 6. Dosya Yükleme İşlemleri

Önemli: Bu işlemler `multipart/form-data` formatında olmalıdır.

- **Logo:** `POST api/upload/logo.php` (form field: `logo`)
- **İç Tesisat Panel Foto:** `POST api/upload/ic-tesisat-foto.php` (form field: `photo`, `panel_id`, `photo_type`)
- **Genel Rapor Görselleri:** `POST api/upload/genel-rapor-gorsel.php` (form field: `images[]`, `report_id`)

---

## 7. CSV Entegrasyonu

**`api/csv/topraklama.php`**
- **Şablon İndirme:** `GET ?action=download&type=5_1`
- **İçe Aktarma:** `POST ?type=5_1&report_id=1` (form field: `csv_file` multipart)

---

## Hata Kodları Referansı

| Hata Kodu | Açıklama |
|---|---|
| `AUTH_REQUIRED` | İstekte token eksik. |
| `AUTH_INVALID` | Token geçersiz (bozuk vb.) |
| `AUTH_EXPIRED` | Token süresi dolmuş. Yeniden giriş yapılmalı. |
| `AUTH_FAILED` | Kullanıcı adı veya şifre yanlış. |
| `FORBIDDEN` | Bu işlemi yapmaya yetkiniz yok. |
| `NOT_FOUND` | İlgili kayıt bulunamadı. |
| `VALIDATION_ERROR` | Zorunlu parametre eksik veya hatalı tip. |
| `NO_ACTIVE_INSTITUTION` | İstekte X-Institution-Id eksik. |
| `SERVER_ERROR` | Beklenmeyen veritabanı veya PHP hatası. |
