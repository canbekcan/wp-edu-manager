# WP EDU Manager (Host)

WordPress tabanlı akademik içerik izleme, otomatik notlandırma ve öğrenci yönetim platformudur[cite: 3]. Öğrencilerin ürettiği içerikleri, revizyon geçmişlerini, SEO metriklerini ve hız analizlerini merkezi bir sunucu üzerinden denetlemenizi sağlar[cite: 3, 9].

> **Önemli Not:** Bu eklenti **Host (Öğretmen)** tarafıdır ve **[WP EDU Client](https://github.com/canbekcan/wp-edu-client)** eklentisi ile entegre çalışır[cite: 1, 3]. Eğitmen kendi ana sitesine `wp-edu-manager` eklentisini kurarken, derse katılan her öğrenci kendi bağımsız WordPress sitesine `wp-edu-client` eklentisini kurmalıdır[cite: 1, 3].

---

## Özellikler

* **Algoritmik Notlandırma:** Kelime sayısı, iç/dış bağlantılar, görseller, eksik ALT etiketleri ve yayın sonrası yapılan değişiklikler (modifications) üzerinden otomatik puanlama yapar[cite: 5, 8, 9].
* **Asenkron İçerik Eşitleme (WP-Cron):** Öğrenci sitelerindeki içerikleri ve revizyonları sunucuyu yormadan arka planda asenkron olarak çeker[cite: 6].
* **Tek Tıkla Giriş (SSO):** 24 saat geçerli zaman aşımı (TTL) ve hash doğrulamalı güvenlik anahtarı ile öğrencilerin Host paneline şifresiz geçiş yapmasını sağlar[cite: 4, 6].
* **Canlı Güncelleme Takibi:** Bağlı öğrenci sitelerinin çekirdek (core), eklenti ve tema güncellemelerini tek tablodan izler[cite: 6, 9].
* **Doğrudan Bildirim Gönderimi:** Öğrenci sitelerinin WordPress panolarına doğrudan yönetici mesajları ve uyarıları iletir[cite: 7].
* **Çok Dilli Altyapı (i18n):** İngilizce (varsayılan) ve Türkçe tam dil desteği içerir.

---

## Mimari ve Sistem Gereksinimleri

* **Öğretmen / Ana Site:** WordPress 5.8+, PHP 7.4+, MySQL 5.7+ (`wp-edu-manager` kurulu olmalıdır)[cite: 3, 6]
* **Öğrenci Siteleri:** WordPress 5.8+, PHP 7.4+ (`wp-edu-client` kurulu olmalıdır)[cite: 1]
* **REST API:** Her iki sunucuda da WordPress REST API uç noktaları erişilebilir olmalıdır[cite: 3, 5].

---

## Kurulum ve Kullanım Talimatları

### 1. Eğitmen Kurulumu (Host)
1. Bu repoyu indirin ve klasör adını `wp-edu-manager` yaparak `/wp-content/plugins/` dizinine yükleyin[cite: 3].
2. WordPress Yönetici Paneli > **Eklentiler** sayfasından **WP EDU Manager (Host)** eklentisini etkinleştirin[cite: 3].
3. Sol menüde beliren **LMS Manager > Semesters** sayfasına gidin[cite: 11].
4. Yeni bir dönem adı (örn. *Güz 2026*), kayıt kodu (örn. *NEWS-F26*), bitiş tarihi ve notlandırma katsayılarını belirleyerek dönemi oluşturun[cite: 8].

### 2. Öğrenci Kurulumu (Client)
1. Öğrenci, kendi WordPress sitesine **WP EDU Client** eklentisini kurup etkinleştirir[cite: 1].
2. Öğrenci panelindeki ayarlar alanına Eğitmenin site adresini (`Host URL`) ve eğitmenin sağladığı `Registration Code` (Kayıt Kodu) bilgisini girerek kaydı tamamlar[cite: 3, 5].
3. Eşleşme sağlandığında öğrenciye özel bir `API Token` üretilir ve Host sitesinde öğrenci adına otomatik olarak bir `Contributor` (İçerik Sağlayıcı) hesabı açılır[cite: 3, 5, 6].

### 3. İçerik Eşitleme ve Denetim
* **Otomatik Tarama:** Sistem her gece 23:50'de otomatik olarak çalışır ve tüm öğrenci sitelerindeki yeni içerikleri, düzenleme sürelerini (WPM) ve revizyon durumlarını çeker[cite: 6, 9].
* **Manuel Tarama:** **LMS Manager > Dashboard** sekmesinden *Fetch Data Now* butonu ile eşitleme anında tetiklenebilir[cite: 10].
* **Denetim Ekranı:** **LMS Manager > Content Audit** sekmesinden tüm yazıların SEO uyumluluğu, değiştirilme bayrakları (Modified/Original) ve hesaplanan notları incelenebilir[cite: 9].

---

## Lisans

Bu proje **MIT Lisansı** altında lisanslanmıştır.

```text
MIT License

Copyright (c) 2026 Can Bekcan

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```