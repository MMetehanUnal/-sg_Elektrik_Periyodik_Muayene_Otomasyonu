# İSG Elektrik Periyodik Muayene Otomasyonu - Android Geliştirme Rehberi

Bu belge, oluşturulan REST API kullanılarak geliştirilecek Native Android uygulaması için mimari kuralları ve geliştirme standartlarını belirler.

## 1. Proje Altyapısı ve Hedefler

- **Platform:** Native Android
- **Dil:** Kotlin
- **Minimum SDK:** 26 (Android 8.0)
- **Target SDK:** 35
- **UI Framework:** Jetpack Compose + Material Design 3

## 2. Mimari (Clean Architecture + MVVM)

Uygulamanın `Clean Architecture` prensiplerine uygun katmanlı bir yapıda olması hedeflenmektedir. Bu yapı hem bakım kolaylığı hem de offline çalışabilirlik (Local DB senkronizasyonu) için kritik öneme sahiptir.

```
app/src/main/java/com/isg/otomasyon/
├── data/              # Veri katmanı (API, Room DB)
├── domain/            # İş kuralları katmanı (Repository Interfaceler, UseCaseler)
├── presentation/      # UI katmanı (Compose, ViewModels)
└── di/                # Hilt Dependency Injection Modülleri
```

## 3. Teknoloji Yığını (Tech Stack)

1. **Ağ İşlemleri (Network):** `Retrofit 2` + `OkHttp 4`
2. **JSON Dönüşümü:** `Kotlinx Serialization` veya `Moshi`
3. **Asenkron İşlemler:** `Kotlin Coroutines` + `Flow`
4. **Bağımlılık Enjeksiyonu:** `Dagger Hilt`
5. **Görsel Yükleme:** `Coil`
6. **Yerel Veritabanı:** `Room Database`
7. **Navigasyon:** `Compose Navigation`
8. **Token/Ayar Saklama:** `DataStore` (EncryptedSharedPreferences önerilir)

## 4. API Entegrasyon Kuralları ve Interceptor Yapısı

API'nin sorunsuz kullanılabilmesi için aşağıdaki yapılandırmaların Retrofit `OkHttpClient` içerisine eklenmesi zorunludur.

### 4.1. Auth Interceptor
Tüm isteklerde `Authorization: Bearer <token>` header'ını otomatik olarak ekler.
*Not: `/auth/login.php` gibi endpoint'ler hariç tutulmalıdır.*

### 4.2. Institution Interceptor
API, kurumları (tesisleri) ayrıştırmak için `X-Institution-Id` header'ına ihtiyaç duyar. Uygulama içinde kullanıcı "Aktif Tesis" seçtiğinde bu ID DataStore'a yazılır. Daha sonra Interceptor bu ID'yi okuyup header'a eklemelidir.

```kotlin
class AuthInterceptor(private val tokenManager: TokenManager) : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        val requestBuilder = chain.request().newBuilder()
        
        tokenManager.getToken()?.let {
            requestBuilder.addHeader("Authorization", "Bearer $it")
        }
        
        tokenManager.getActiveInstitutionId()?.let {
            requestBuilder.addHeader("X-Institution-Id", it.toString())
        }
        
        return chain.proceed(requestBuilder.build())
    }
}
```

### 4.3. Token Süresi Dolma Durumu (401 Unauthorized)
Interceptor veya Authenticator üzerinden, dönen yanıt `401` ise kullanıcı oturumu otomatik kapatılmalı ve `LoginScreen`'e yönlendirilmelidir. Token yenileme (refresh token) mekanizması bu API'de desteklenmemektedir, doğrudan çıkış yaptırılacaktır.

## 5. Offline Çalışma (Room Database) Stratejisi

Sahada (tesislerde) her zaman stabil bir internet bağlantısı olmayabilir. Form verilerinin kaybolmaması için "Offline-First" yaklaşımı izlenmelidir.

1. **İçerik Çekme (Fetch):** Uygulama açıldığında kurumlar, yetkili kişiler ve cihazlar arka planda Room veritabanına eşitlenir (Sync).
2. **Okuma:** UI (Compose) daima `Flow` üzerinden Room DB'yi dinler. Veriler öncelikle Local DB'den alınır.
3. **Yazma (Mutation):** 
   - Yeni bir kontrol formu oluşturulduğunda önce Local DB'ye kaydedilir ve bir `sync_status = PENDING` işareti konur.
   - İnternet bağlantısı varsa, Arka Plan Servisi (WorkManager) bu veriyi alıp API'ye postalar.
   - Başarılı olursa Local DB güncellenir (`sync_status = SYNCED`).

## 6. UI / UX Standartları (Jetpack Compose)

- **Material 3 (M3):** Uygulama M3 bileşenleri ile donatılacaktır.
- **Tema:** Açık ve Koyu (Dark) tema desteği bulunacaktır.
- **Durum (State) Yönetimi:** `StateFlow` kullanılarak UI State (Loading, Success, Error) durumları kontrol edilmelidir.
- **Doğrulama (Validation):** Form alanlarındaki doğrulama (boş bırakılamaz vs.) işlemleri ViewModel üzerinde yapılacak, hatalar Compose UI üzerinde TextField altında gösterilecektir.

```kotlin
// Örnek UI State
sealed class UiState<out T> {
    object Loading : UiState<Nothing>()
    data class Success<T>(val data: T) : UiState<T>()
    data class Error(val message: String) : UiState<Nothing>()
}
```

## 7. Hata Yönetimi

Sunucudan dönen özel hata kodları (Örn: `VALIDATION_ERROR`, `INSTITUTION_ACCESS_DENIED`) parse edilecek ve kullanıcı dostu Türkçe mesajlara dönüştürülerek gösterilecektir. (Örn: Snackbar veya AlertDialog ile).

## 8. Formların Yapısı ve Karmaşıklığı

Projedeki rapor formları (Özellikle `İç Tesisat Kontrol Formu`) oldukça detaylıdır (yaklaşık 45 alan + çoklu panolar).
- Uygulama tarafında formlar sekme (Tabs) veya adım adım sihirbaz (Wizard/Stepper) yapısıyla tasarlanmalı, kullanıcının gözü korkutulmamalıdır.
- "Fotoğraf Yükleme" kısımlarında kamera entegrasyonu (CameraX) kullanılıp anında rapor panolarına ilişkilendirilebilmelidir.
