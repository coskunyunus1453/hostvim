<?php

return [
    'title' => 'Hosting Taşıma Rehberi',
    'slug' => 'hosting-tasima-rehberi',
    'category_slug' => 'hosting-rehberi',
    'image' => 'blog-hosting-tasima.png',
    'days_ago' => 0,
    'excerpt' => 'Hosting firması değiştirmek korkutucu görünür ama doğru adımlarla sorunsuz olur. Yedekleme, DNS ve test sürecini anlattık.',
    'meta_title' => 'Hosting Taşıma Rehberi | Adım Adım Migration',
    'meta_description' => 'Hosting taşıma nasıl yapılır? Dosya yedekleme, veritabanı transferi, DNS güncelleme ve kesintisiz geçiş rehberi.',
    'meta_keywords' => 'hosting taşıma, hosting migration, site taşıma, hosting değiştirme',
    'content' => <<<'HTML'
<h2>Neden hosting değiştirirsiniz?</h2>
<p>Yavaş site, kötü destek, gizli ücretler veya sürekli kesinti… Hosting değiştirmek normal bir süreçtir. Doğru planlama ile ziyaretçileriniz fark etmeden taşınabilirsiniz. Bu rehber WordPress ve genel PHP siteleri için geçerlidir.</p>

<h2>Taşıma öncesi hazırlık</h2>
<ol>
<li><strong>Yeni hosting sipariş edin</strong> — Paket kaynaklarının eskisinden iyi veya eşdeğer olduğundan emin olun.</li>
<li><strong>Tam yedek alın</strong> — Dosyalar (FTP/SFTP) + veritabanı (phpMyAdmin veya mysqldump).</li>
<li><strong>E-posta listesini not edin</strong> — MX kayıtları değişecekse posta kutularını da taşıyın.</li>
<li><strong>Düşük trafik saati seçin</strong> — Gece veya hafta sonu DNS yayılımı için idealdir.</li>
<li><strong>TTL değerini düşürün</strong> — Taşımadan 24 saat önce DNS TTL'i 300 saniyeye indirin.</li>
</ol>

<h2>Adım 1: Dosyaları yeni sunucuya aktarın</h2>
<p>public_html (veya web root) klasörünün tamamını SFTP, rsync veya hosting panelindeki "taşıma aracı" ile kopyalayın. <code>.htaccess</code>, <code>wp-config.php</code> ve gizli dosyaları atlamayın. Dosya izinleri genelde klasör 755, dosya 644'tür.</p>

<h2>Adım 2: Veritabanını import edin</h2>
<p>Yeni panelde boş veritabanı oluşturun. SQL dump dosyasını phpMyAdmin veya komut satırı ile import edin:</p>
<pre><code>mysql -u kullanici -p veritabani_adi &lt; yedek.sql</code></pre>
<p>WordPress'te <code>wp-config.php</code> içindeki DB_NAME, DB_USER, DB_PASSWORD ve DB_HOST değerlerini yeni bilgilerle güncelleyin.</p>

<h2>Adım 3: Geçici test (hosts dosyası)</h2>
<p>DNS değiştirmeden önce bilgisayarınızın hosts dosyasına yeni sunucu IP'sini yazarak siteyi test edin. Giriş, formlar, ödeme ve görselleri kontrol edin.</p>

<h2>Adım 4: DNS güncelleme</h2>
<p>Test başarılıysa domain A kaydını veya nameserver'ları yeni hostinge yönlendirin. Yayılım 1–48 saat sürebilir; TTL düşükse daha hızlı olur. Eski hostingi hemen kapatmayın; en az bir hafta paralel tutun.</p>

<h2>Adım 5: SSL ve yönlendirmeler</h2>
<p>Yeni sunucuda Let's Encrypt sertifikası alın. HTTP'den HTTPS'e 301 yönlendirme çalıştığını doğrulayın. Search Console'da site haritasını yeniden gönderin.</p>

<h2>Sık karşılaşılan sorunlar</h2>
<h3>Beyaz ekran (WSOD)</h3>
<p>PHP hata loglarına bakın; genelde yanlış dosya yolu veya bellek limitidir.</p>

<h3>Görseller açılmıyor</h3>
<p>wp-content/uploads izinleri veya yanlış site URL ayarı (siteurl/home) kontrol edin.</p>

<h3>E-posta gitmiyor</h3>
<p>MX kayıtları hâlâ eski sunucuya işaret ediyor olabilir.</p>

<h2>Profesyonel taşıma hizmeti</h2>
<p>Teknik bilginiz yoksa Hostvim destek ekibi ücretsiz taşıma yardımı sunabilir. Ticket açarak eski panel bilgilerinizi paylaşmanız yeterlidir.</p>

<h2>Taşıma sonrası kontrol listesi</h2>
<ul>
<li>Tüm sayfalar 200 dönüyor mu?</li>
<li>İletişim formu ve ödeme test edildi mi?</li>
<li>Google Analytics / Search Console çalışıyor mu?</li>
<li>Yedekleme yeni sunucuda otomatik mi?</li>
<li>Eski hosting iptal edildi mi?</li>
</ul>

<h2>rsync ile hızlı dosya transferi</h2>
<p>Büyük sitelerde SFTP yerine rsync daha hızlı ve kesintiye dayanıklıdır:</p>
<pre><code>rsync -avz --progress eski:/home/user/public_html/ yeni:/var/www/site/public_html/</code></pre>
<p>İlk senkronizasyon uzun sürer; sonraki çalıştırmalarda sadece değişen dosyalar aktarılır.</p>

<h2>Veritabanı URL güncelleme</h2>
<p>Domain değiştiyse WordPress tablolarında eski URL kalmış olabilir. WP-CLI ile toplu güncelleme:</p>
<pre><code>wp search-replace 'https://eski.com' 'https://yeni.com' --all-tables</code></pre>
<p>phpMyAdmin'de manuel replace risklidir; mutlaka yedek alın.</p>

<h2>Taşıma zaman çizelgesi örneği</h2>
<table>
<thead><tr><th>Gün</th><th>İşlem</th></tr></thead>
<tbody>
<tr><td>D-2</td><td>Yeni hosting hazır, TTL düşür</td></tr>
<tr><td>D-1</td><td>Tam yedek, dosya+DB aktarımı</td></tr>
<tr><td>D-0 gece</td><td>Hosts testi, DNS değişikliği</td></tr>
<tr><td>D+1</td><td>SSL, e-posta, izleme kontrolü</td></tr>
<tr><td>D+7</td><td>Eski hosting iptal</td></tr>
</tbody>
</table>

<h2>Zero-downtime taşıma tekniği</h2>
<p>Yüksek trafikli sitelerde DNS değişmeden önce eski sunucuda bakım modu açmadan replikasyon yapılır. Son aşamada TTL düşükken DNS flip yapılır; veritabanında son dakika değişiklikleri rsync ile senkronize edilir. Bu ileri seviye yöntem ortalama blog için gerekmez.</p>

<h2>E-posta taşıma detayı</h2>
<p>IMAP kullanıyorsanız imapsync aracı posta kutularını sunucular arası kopyalar. MX kaydını değiştirmeden önce yeni sunucuda test kutusu oluşturup gönderim-alım deneyin. SPF kaydını yeni sunucu IP'sine göre güncelleyin.</p>

<h2>Taşıma sonrası SEO</h2>
<p>Google Search Console'da adres değişikliği aracı (domain değişiyorsa) veya sitemap yeniden gönderimi yapın. 301 yönlendirmelerin kalıcı olduğundan emin olun. 404 hatalarını ilk hafta düzenli kontrol edin.</p>

<!-- GENISLETME_ISARETI -->
<h2>Taşıma öncesi paydaş iletişimi</h2>
<p>E-ticaret sitesi taşırken müşterilere kısa bakım penceresi duyurusu yapın. Sosyal medyada "geçici yavaşlık" bilgisi güven oluşturur. Taşıma sonrası ödeme ve sipariş akışını manuel test edin.</p>
<h2>Rollback planı</h2>
<p>DNS değişikliğinden önce eski sunucunun tam yedeğini ve DNS eski değerlerini not edin. Sorun çıkarsa DNS'i geri alarak 1 saat içinde eski ortama dönebilirsiniz. Panik yapmadan checklist takip edin.</p>
<h2>Profesyonel destek ne zaman şart?</h2>
<p>Özel yazılım, çoklu veritabanı, microservice veya 50 GB üzeri dosya varsa profesyonel taşıma hizmeti alın. Hostvim ücretsiz taşıma kotası standart WordPress siteleri için yeterlidir; karmaşık projelerde özel teklif isteyin.</p><!-- GENISLETME2 -->
<h2>Checklist PDF mantığı</h2>
<p>Taşıma günü yazdırılabilir checklist kullanın: yedek alındı mı, DB import edildi mi, wp-config güncellendi mi, SSL aktif mi, DNS değişti mi, e-posta test edildi mi, Analytics çalışıyor mu. Her maddeyi işaretleyerek ilerleyin; hafıza yerine kağıt güvenilirdir.</p><!-- GENISLETME3 -->
<p>Doğru planlanan taşıma bir kez yapılır, yıllarca rahat edersiniz. Kötü sağlayıcıda kalmak tekrarlayan stres demektir.</p><p>Hostvim'e geçiş için destek talebi açın; dosya ve veritabanı transferinde rehberlik ederiz.</p>
<!-- GENISLETME4 -->
<h2>Taşıma sonrası izleme</h2><p>Taşımadan sonraki 7 gün uptime monitörü (UptimeRobot, Pingdom) kurun. 5xx hata oranı, ortalama yanıt süresi ve SSL bitiş tarihini izleyin. Google Analytics'te ani trafik düşüşü DNS veya yönlendirme hatasına işaret edebilir.</p>
<!-- GENISLETME5 -->
<h2>DNS kayıt yedekleme</h2><p>Taşımadan önce tüm DNS kayıtlarını (A, AAAA, CNAME, MX, TXT) ekran görüntüsü veya zone file export ile saklayın. SPF ve DKIM TXT kayıtlarını unutmak e-posta kesintisine yol açar. Google Workspace kullanıyorsanız MX öncelik değerlerini aynen taşıyın.</p>
<!-- GENISLETME6 -->
<p>Taşıma sonrası ilk ay eski hosting faturasını iptal etmeyi unutmayın; çift ödeme yapmayın. Yedekleri en az 30 gün saklayın.</p><p>Hostvim'e hoş geldiniz; taşıma sonrası hız farkını kısa sürede fark edeceksiniz.</p>
<!-- GENISLETME7 -->
<h2>Gece taşıması avantajı</h2><p>02:00–06:00 arası trafik düşüktür; DNS propagation sırasında etkilenen kullanıcı sayısı azalır. Hafta sonu gece kombinasyonu birçok site için idealdir.</p>
<!-- GENISLETME8 -->
<p>Taşıma bir kez doğru yapılırsa bir daha düşünmezsiniz. Cesaret edin; daha iyi hosting deneyimi bekliyor.</p>
<!-- FINALBOOST -->
<p>Taşıma checklist'ini yazdırın, adım adım ilerleyin. Hostvim destek ekibi süreç boyunca yanınızdadır.</p>
<!-- FINALBOOST10 -->
<p>Hostvim'e taşınmak için destek talebi açın; süreci birlikte yönetelim. Daha hızlı ve güvenilir hosting bir gece uzağınızda.</p>
<!-- HOSTVIM_FOOTER_PARA -->
<p>Bu rehberde anlattığımız konular hosting ve domain dünyasının temel taşlarıdır. Teknoloji değişse de iyi altyapı, güvenlik ve destek her zaman önceliklidir. Hostvim olarak Türkiye ve global müşterilerimize şeffaf fiyatlandırma, modern veri merkezi altyapısı ve Türkçe teknik destek sunuyoruz. Sorularınız için iletişim sayfamızdan veya müşteri panelinden bize ulaşabilirsiniz. Blog yazılarımızı takip ederek sektörde güncel kalın; yeni başlayanlar ve profesyoneller için içerik üretmeye devam ediyoruz.</p>
<h2>Sonuç</h2>
<p>Hosting taşıması planlı yapıldığında stresli değildir. Yedek, test, DNS — üç adımı atlamayın. <a href="/web-hosting">Hostvim hosting</a> paketlerine geçiş için destek ekibimizle iletişime geçebilirsiniz.</p>
HTML,
];
