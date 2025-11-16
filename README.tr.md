# Magento 2 - Ikas Ürün İçe Aktarma Modülü

Ikas e-ticaret platformundan Magento 2'ye ürün aktarmak için profesyonel, ölçeklenebilir modül.

**Modül Adı:** `Mucan54_IkasImport`
**Namespace:** `Mucan54\IkasImport`
**Versiyon:** 1.0.0

---

## Genel Bakış

Bu modül, Ikas e-ticaret platformundan Magento 2'ye ürün aktarımı için tam özellikli, üretim ortamına hazır bir çözüm sunar. **Kuyruk tabanlı mimari** sayesinde 100.000+ ürünü timeout olmadan işleyebilir.

### Temel Özellikler

- ✅ **Kuyruk Tabanlı İçe Aktarma**: 50'şerli paketler halinde asenkron işleme
- ✅ **Sınırsız Ürün Kapasitesi**: Hafıza verimli generator kullanımı
- ✅ **Türkçe Karakter Desteği**: Tam UTF-8 ve karakter dönüşümü
- ✅ **Dinamik Özellik Oluşturma**: HTML açıklamalardan otomatik özellik çıkarma
- ✅ **Akıllı Stok Yönetimi**: StockRegistryInterface ile doğru stok güncelleme
- ✅ **Kategori Hiyerarşisi**: İç içe kategorileri otomatik oluşturur
- ✅ **Asenkron Resim İşleme**: Ayrı kuyrukta resim indirme
- ✅ **Kapsamlı Doğrulama**: Çok katmanlı veri kontrolü
- ✅ **Detaylı Loglama**: Ayrı log dosyası ile izleme
- ✅ **CLI Desteği**: Komut satırı araçları
- ✅ **Admin Paneli**: Kullanıcı dostu yönetim arayüzü

---

## Kurulum

### Composer ile (Önerilen)

```bash
composer require mucan54/magento2-ikas-importer
php bin/magento module:enable Mucan54_IkasImport
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:clean
```

### Manuel Kurulum

1. Dizin oluşturun: `app/code/Mucan54/IkasImport`
2. Tüm modül dosyalarını bu dizine kopyalayın
3. Komutları çalıştırın:

```bash
php bin/magento module:enable Mucan54_IkasImport
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:clean
```

---

## Kuyruk Tabanlı Mimari

### Nasıl Çalışır?

1. **CSV Ayrıştırma**: CSV dosyası okunur ve 50'şerli paketlere bölünür
2. **Ürün Kuyruğu**: Her paket `ikas.product.import.batch` kuyruğuna gönderilir
3. **Ürün İşleme**: Consumer paketleri işler, ürünleri oluşturur/günceller
4. **Resim Kuyruğu**: Her ürün için `ikas.product.images` kuyruğuna mesaj gönderilir
5. **Resim İşleme**: Ayrı consumer resimleri indirir ve atar

### Avantajlar

- 🚀 **Sınırsız Ölçeklenebilirlik**: 1 milyon ürün bile sorunsuz
- 💪 **Hata Toleransı**: Başarısız işlemler tekrar denenebilir
- ⚡ **Paralel İşleme**: Birden fazla consumer aynı anda çalışabilir
- ⏱️ **Timeout Yok**: Asenkron işleme
- 🛡️ **Bağımsız Hatalar**: Resim hatası tüm işlemi durdurmaz
- 📊 **İzlenebilirlik**: Kuyruk durumunu görebilirsiniz
- ⚙️ **Kaynak Kontrolü**: Yapılandırılabilir paket boyutları

---

## Kullanım

### 1. CSV Hazırlama

Ikas formatında CSV dosyanız olmalı (25 sütun):

```csv
Ürün Grup ID,Varyant ID,İsim,Açıklama,Satış Fiyatı,İndirimli Fiyatı,SKU,Kategoriler,Resim URL,Varyant Aktiflik...
```

Örnek satır:
```csv
1,101,Cotton Gold Kırmızı,"<ul><li>Kullanım Alanı : Hırka, yelek</li></ul>",25.50,22.00,COTGOLD-RED-001,İplikler/Cotton Gold,https://example.com/image.jpg,Aktif
```

### 2. CSV'yi Kuyruğa Yükle

```bash
# Varsayılan (50'şerli paketler)
php bin/magento ikas:import:run /path/to/products.csv

# Özel paket boyutu
php bin/magento ikas:import:run /path/to/products.csv -b 100

# Sadece doğrulama
php bin/magento ikas:import:run /path/to/products.csv --validate-only
```

### 3. Consumer'ları Başlat

```bash
# Ürün işleyici (terminal 1)
php bin/magento queue:consumers:start ikas.product.import.batch.consumer

# Resim işleyici (terminal 2)
php bin/magento queue:consumers:start ikas.product.images.consumer
```

### 4. Üretim Ortamı için Supervisor

`/etc/supervisor/conf.d/ikas-import.conf`:

```ini
[program:ikas_product_consumer]
command=php /var/www/magento/bin/magento queue:consumers:start ikas.product.import.batch.consumer --max-messages=100
directory=/var/www/magento
autostart=true
autorestart=true
user=www-data
numprocs=2
process_name=%(program_name)s_%(process_num)02d
stdout_logfile=/var/log/supervisor/ikas_product.log
stderr_logfile=/var/log/supervisor/ikas_product_error.log

[program:ikas_image_consumer]
command=php /var/www/magento/bin/magento queue:consumers:start ikas.product.images.consumer --max-messages=200
directory=/var/www/magento
autostart=true
autorestart=true
user=www-data
numprocs=1
stdout_logfile=/var/log/supervisor/ikas_image.log
stderr_logfile=/var/log/supervisor/ikas_image_error.log
```

Sonra:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

---

## Yapılandırma

**Stores > Configuration > Ikas Import > Configuration**

### Genel Ayarlar
- **Modülü Etkinleştir**: Aktif/pasif
- **Paket Boyutu**: Kuyruk paket boyutu (varsayılan: 50)
- **Kategori Kökü**: Ana kategori (örn: "İplikler")
- **Maks. Çalışma Süresi**: Saniye cinsinden (varsayılan: 3600)

### Özellik Ayarları
- **Dinamik Özellik Oluştur**: HTML'den otomatik özellik
- **Özellik Ön Eki**: Özellik kodu ön eki (varsayılan: `ikas_`)
- **Özellik Grubu**: Grup adı (varsayılan: "Ikas Attributes")
- **Kara Liste**: Yok sayılacak anahtar kelimeler
- **Min. Anahtar Uzunluğu**: Minimum karakter (varsayılan: 2)

### Stok Ayarları
- **Stok Yönetimi**: Stok takibi yap/yapma
- **Arka Siparişler**: İzin ver/verme
- **Varsayılan Kaynak Kodu**: MSI için (varsayılan: "default")
- **Stokta Yok Eşiği**: Eşik değeri (varsayılan: 0)

### Resim Ayarları
- **Asenkron İşleme**: Kuyruk kullan
- **Kuyruk Bağlantısı**: `db` veya `amqp` (RabbitMQ)
- **Maks. Resim**: Ürün başına (varsayılan: 10)
- **İzin Verilen Uzantılar**: jpg,jpeg,png,gif,webp
- **İndirme Timeout**: Saniye (varsayılan: 30)

### Kategori Ayarları
- **Otomatik Oluştur**: Eksik kategorileri oluştur
- **Varsayılan Durum**: Yeni kategoriler aktif
- **Menüye Dahil Et**: Navigasyonda göster

### Doğrulama Ayarları
- **SKU Doğrula**: Format kontrolü yap
- **Fiyat Doğrula**: Aralık kontrolü
- **URL Doğrula**: Resim URL'lerini kontrol et
- **Zorunlu Alanlar**: sku,name,price
- **Min/Maks Fiyat**: Fiyat aralığı

### Gelişmiş Ayarlar
- **Debug Modu**: Detaylı loglama
- **Bellek Limiti**: PHP limiti (örn: 512M, 1G)
- **Önbelleği Temizle**: İşlem sonrası temizle
- **Yeniden İndeksle**: Otomatik indeksleme
- **Log Seviyesi**: DEBUG, INFO, WARNING, ERROR, CRITICAL

---

## Dinamik Özellik Oluşturma

### HTML Formatı

Açıklamada şu formatta özellikler olmalı:

```html
<ul>
  <li>Kullanım Alanı : Hırka, yelek, süveter</li>
  <li>Karışım : % 45 Akrilik - % 55 Pamuk</li>
  <li>Metraj : 100 gr - 330 m</li>
  <li>Yıkama : 30 derece hassas yıkama</li>
</ul>
```

### Özellik Kodu Üretimi

Türkçe karakterler ASCII'ye dönüştürülür:

| Türkçe | ASCII |
|--------|-------|
| ı, İ   | i     |
| ş, Ş   | s     |
| ğ, Ğ   | g     |
| ç, Ç   | c     |
| ö, Ö   | o     |
| ü, Ü   | u     |

**Örnekler:**
- "Kullanım Alanı" → `ikas_kullanim_alani`
- "Karışım" → `ikas_karisim`
- "Metraj" → `ikas_metraj`
- "Yıkama" → `ikas_yikama`

### Kara Liste

İstenmeyen anahtarları yapılandırmadan ekleyin:

```
uyarı,not,artikel no,açıklama,uyari
```

---

## Kategori Yönetimi

### Hiyerarşi Oluşturma

Kategori yolu: `İplikler/Cotton Gold/Renk Kartelası`

Oluşturulacaklar:
1. **İplikler** (kök)
2. **Cotton Gold** (İplikler'in altı)
3. **Renk Kartelası** (Cotton Gold'un altı)

### URL Anahtarları

Türkçe karakterler dönüştürülür:
- "İplikler" → `iplikler`
- "Çocuk Ürünleri" → `cocuk-urunleri`
- "Örme İplikleri" → `orme-iplikleri`

### Önbellekleme

Kategori sorguları oturum boyunca bellekte tutulur, gereksiz veritabanı sorguları önlenir.

---

## Performans ve Ölçeklenebilirlik

### Büyük Veri Setleri

| Ürün Sayısı | Önerilen Yapılandırma |
|-------------|----------------------|
| < 1,000     | Tek consumer yeterli |
| 1,000-10,000| 2 product + 1 image consumer |
| 10,000-50,000| 4 product + 2 image consumer |
| 50,000+     | 8 product + 4 image consumer |

### Bellek Kullanımı

- **CSV Ayrıştırma**: ~50 MB (generator kullanımı)
- **50 Ürün Paketi**: ~10-20 MB
- **Toplam Tahmini**: ~512 MB ile 100,000 ürün işlenebilir

### İşlem Hızı

- **CSV Ayrıştırma**: ~10,000 satır/saniye
- **Ürün İşleme**: ~20-50 ürün/saniye (consumer başına)
- **Resim İndirme**: ~5-10 resim/saniye (network'e bağlı)

**Örnek**: 50,000 ürün + 150,000 resim
- CSV yükleme: ~5 dakika
- Ürün işleme (4 consumer): ~30-45 dakika
- Resim işleme (2 consumer): ~4-8 saat (paralel)

---

## Loglama

### Log Dosyası

Tüm işlemler loglanır: `var/log/ikas_import.log`

### Log Seviyeleri

```
[2025-11-16 12:30:45] IkasImport.INFO: Product batch published {"batch_number":1,"product_count":50}
[2025-11-16 12:30:50] IkasImport.INFO: Product created {"sku":"PROD001","product_id":12345}
[2025-11-16 12:30:55] IkasImport.ERROR: Failed to download image {"url":"...","error":"..."}
```

### Log İzleme

```bash
# Canlı log izleme
tail -f var/log/ikas_import.log

# Son hatalar
grep ERROR var/log/ikas_import.log | tail -20

# İstatistikler
grep "batch published" var/log/ikas_import.log | wc -l
```

---

## Sorun Giderme

### Sık Karşılaşılan Sorunlar

**1. Bellek Doldu**
```bash
# Çözüm: Bellek limitini artır
php -d memory_limit=1G bin/magento ikas:import:run file.csv
```

**2. Consumer Çalışmıyor**
```bash
# Kuyruk durumunu kontrol et
php bin/magento queue:consumers:list

# Consumer'ı manuel başlat
php bin/magento queue:consumers:start ikas.product.import.batch.consumer
```

**3. Stok Güncellenmiyor**
- SKU'nun doğru olduğundan emin olun
- Ürünün mevcut olduğunu kontrol edin
- `var/log/ikas_import.log` dosyasını inceleyin

**4. Resimler İndirilmiyor**
- Image consumer çalışıyor mu kontrol edin
- URL'lere erişilebildiğinden emin olun
- İzin verilen uzantıları kontrol edin
- Log dosyasına bakın

**5. Türkçe Karakterler Bozuk**
- CSV'nin UTF-8 kodlamalı olduğundan emin olun
- BOM (Byte Order Mark) varsa modül otomatik kaldırır
- Veritabanı charset'inin UTF-8 olduğunu doğrulayın

**6. Kategoriler Oluşmuyor**
- "Otomatik Oluştur" ayarını aktif edin
- Kategori yol formatını kontrol edin (`/` ayracı)
- ACL izinlerini kontrol edin

**7. Özellikler Oluşmuyor**
- "Dinamik Özellik Oluşturma" aktif mi?
- Kara listede değil mi kontrol edin
- HTML formatı doğru mu?

### Debug Modu

Detaylı loglama için:
**Stores > Configuration > Ikas Import > Advanced > Debug Mode = Yes**

### Logları Kontrol Et

```bash
tail -f var/log/ikas_import.log
tail -f var/log/system.log
tail -f var/log/exception.log
```

---

## Güvenlik

### Dosya Yükleme
- Sadece .csv uzantısı kabul edilir
- MIME type kontrolü yapılır
- Dosya boyutu limiti (yapılandırılabilir)
- Güvenli dizinde saklanır

### Resim İndirme
- Sadece http/https protokolü
- MIME type doğrulaması
- Dosya boyutu limiti
- Timeout koruması
- Zararlı içerik taraması önerilir

### Erişim Kontrolü
- ACL ile yetkilendirme
- Rol bazlı izinler
- Aktivite logları
- Rate limiting (yapılandırılabilir)

---

## Gereksinimler

- **Magento**: 2.4.x
- **PHP**: 7.4 / 8.0 / 8.1 / 8.2
- **MySQL**: 5.7+ / MariaDB 10.2+
- **Composer**: Yüklü
- **İsteğe Bağlı**: RabbitMQ (daha iyi performans için)

---

## Lisans

MIT License - Copyright © 2025 Mucan54

---

## Destek

Sorularınız ve önerileriniz için:
- **Email**: info@mucan54.com
- **Issues**: https://github.com/mucan54/magento2-ikas-importer/issues

---

## Değişiklik Günlüğü

### Versiyon 1.0.0 (2025-11-16)

**İlk Sürüm**
- Tam kuyruk tabanlı mimari
- 50'şerlik ürün paketleri
- Ayrı resim kuyruğu
- Dinamik özellik oluşturma
- Kategori hiyerarşisi
- Türkçe karakter desteği
- Kapsamlı doğrulama
- CLI araçları
- Admin paneli
- Detaylı loglama

---

## Katkıda Bulunanlar

Mucan54 tarafından Magento topluluğu için ❤️ ile geliştirilmiştir.

---

## Yol Haritası

- [ ] Web UI ile progress bar
- [ ] İmport geçmişi kaydı
- [ ] Otomatik zamanlanmış importlar (cron)
- [ ] FTP/SFTP'den otomatik çekme
- [ ] Configurable product desteği
- [ ] Bundle product desteği
- [ ] CSV export fonksiyonu
- [ ] Bulk ürün güncelleme
- [ ] MSI (Multi Source Inventory) desteği
- [ ] REST API endpoint'leri

---

**🚀 Mutlu İçe Aktarmalar!**
