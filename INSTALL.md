# 📦 DviceBilet Kurulum Kılavuzu

Bu kılavuz, DviceBilet projesini Docker ile çalıştırmak için gereken adımları içerir.

## 🚀 Hızlı Başlangıç (3 Adımda)

### 1️⃣ Docker Desktop'ı Kurun

**Windows:**
1. [Docker Desktop for Windows](https://www.docker.com/products/docker-desktop) indirin
2. İndirilen `.exe` dosyasını çalıştırın
3. Kurulum tamamlandıktan sonra bilgisayarı yeniden başlatın
4. Docker Desktop'ı açın ve başlamasını bekleyin

**macOS:**
1. [Docker Desktop for Mac](https://www.docker.com/products/docker-desktop) indirin
2. `.dmg` dosyasını açın ve Docker'ı Applications klasörüne sürükleyin
3. Docker Desktop'ı başlatın


### 2️⃣ Projeyi İndirin

**GitHub'dan klonlayın:**
```bash
git clone https://github.com/dvicewashere/bilet-satin-alma.git
cd dvice_bilet
```

**veya ZIP olarak indirin:**
1. GitHub sayfasında "Code" butonuna tıklayın
2. "Download ZIP" seçeneğini seçin
3. ZIP dosyasını çıkarın
4. Klasöre terminal/komut satırından girin

### 3️⃣ Projeyi Çalıştırın

**Windows PowerShell veya Cmd:**
```powershell
# Development ortamı için (önerilen)
docker-compose -f docker-compose.dev.yml up -d

# Başarıyla başladığını kontrol edin
docker-compose -f docker-compose.dev.yml ps
```

**macOS/Linux Terminal:**
```bash
# Development ortamı için (önerilen)
docker-compose -f docker-compose.dev.yml up -d

# Başarıyla başladığını kontrol edin
docker-compose -f docker-compose.dev.yml ps
```

### 4️⃣ Tarayıcıdan Erişin

Tarayıcınızda şu adresi açın:
```
http://localhost:8080
```

## ✅ Kurulum Kontrol Listesi

İlk kurulum için kontrol edin:

- [ ] Docker Desktop kurulu ve çalışıyor mu?
  ```bash
  docker --version
  docker-compose --version
  ```

- [ ] Proje dosyaları indirildi mi?
  ```bash
  ls -la  # macOS/Linux
  dir     # Windows
  ```

- [ ] Container'lar çalışıyor mu?
  ```bash
  docker-compose -f docker-compose.dev.yml ps
  ```

- [ ] Web sitesi açılıyor mu?
  - http://localhost:8080 adresini test edin



## 🔧 İlk Çalıştırmada Yapılacaklar

### 1. Container'ların Durumunu Kontrol Edin

```bash
docker-compose -f docker-compose.dev.yml ps
```

Çıktı şöyle olmalı:
```
NAME                 IMAGE             STATUS              PORTS
dvicebilet-web-dev   dvice_bilet-web   Up (healthy)        0.0.0.0:8080->80/tcp
dvicebilet-db-dev    alpine:latest     Up                  
```

### 2. Logları Kontrol Edin

```bash
# Tüm logları görüntüleyin
docker-compose -f docker-compose.dev.yml logs

# Sadece web container loglarını görüntüleyin
docker-compose -f docker-compose.dev.yml logs web

# Logları canlı izleyin
docker-compose -f docker-compose.dev.yml logs -f
```

### 3. Veritabanını Kontrol Edin

Veritabanı otomatik olarak oluşturulur ve örnek verilerle doldurulur:
- 4 otobüs firması (Metro, Ulusoy, Varan, Pamukkale)
- Test kullanıcıları (admin, yolcu, firma yöneticileri)
- Örnek seferler
- İndirim kuponları



## 🆘 Sorun mu Yaşıyorsunuz?

### Port 8080 Kullanımda

```bash
# Windows'ta kullanılan portu bulun
netstat -ano | findstr :8080

# macOS/Linux'ta kullanılan portu bulun
lsof -i :8080

# Çözüm: Container'ları yeniden başlatın
docker-compose -f docker-compose.dev.yml down
docker-compose -f docker-compose.dev.yml up -d
```

### Container Başlamıyor

```bash
# Eski container'ları temizleyin
docker-compose -f docker-compose.dev.yml down

# Volumes'leri de temizleyin (verileri siler!)
docker-compose -f docker-compose.dev.yml down -v

# Yeniden başlatın
docker-compose -f docker-compose.dev.yml up -d
```

### Sayfa Açılmıyor

1. Docker Desktop'ın çalıştığından emin olun
2. Container'ların "healthy" durumda olduğunu kontrol edin:
   ```bash
   docker-compose -f docker-compose.dev.yml ps
   ```
3. Firewall/Antivirus yazılımınızı kontrol edin
4. Başka bir port kullanmayı deneyin:
   - `docker-compose.dev.yml` dosyasında `ports: - "3000:80"` olarak değiştirin
   - http://localhost:3000 adresine gidin

### PHP Kodu Görünüyor (Sayfa İşlenmiyor)

```bash
# Web container'ını yeniden başlatın
docker-compose -f docker-compose.dev.yml restart web

# 10 saniye bekleyin ve yeniden deneyin
```


```

## 🛑 Projeyi Durdurma

```bash
# Container'ları durdurun (veriler korunur)
docker-compose -f docker-compose.dev.yml stop

# Container'ları tamamen kaldırın (veriler korunur)
docker-compose -f docker-compose.dev.yml down

# Her şeyi temizleyin (veriler dahil)
docker-compose -f docker-compose.dev.yml down -v
rm bus_tickets.db
```



## 💡 İpuçları

1. **İlk çalıştırma uzun sürer:** Docker image'ları indirilir (5-10 dakika)
2. **Sonraki başlatmalar hızlıdır:** Image'lar zaten mevcut olacak
3. **Kod değişiklikleri otomatik yansır:** Container'ı yeniden başlatmaya gerek yok
4. **Veritabanı kalıcıdır:** Container'ı silseniz bile `bus_tickets.db` dosyası korunur

## ✅ Kurulum Tamamlandı!

Tebrikler! DviceBilet sistemi artık çalışıyor. 

🌐 **http://localhost:8080** adresinden erişebilirsiniz.

İyi kullanımlar! Muhammed Harun ŞEKER 🎉

