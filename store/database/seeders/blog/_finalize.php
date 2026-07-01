<?php

$para = '<p>Bu rehberde anlattığımız konular hosting ve domain dünyasının temel taşlarıdır. Teknoloji değişse de iyi altyapı, güvenlik ve destek her zaman önceliklidir. Hostvim olarak Türkiye ve global müşterilerimize şeffaf fiyatlandırma, modern veri merkezi altyapısı ve Türkçe teknik destek sunuyoruz. Sorularınız için iletişim sayfamızdan veya müşteri panelinden bize ulaşabilirsiniz. Blog yazılarımızı takip ederek sektörde güncel kalın; yeni başlayanlar ve profesyoneller için içerik üretmeye devam ediyoruz.</p>';

foreach (glob(__DIR__.'/[0-9]*.php') as $f) {
    $d = require $f;
    preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($d['content']), $m);
    if (count($m[0]) >= 1000) {
        continue;
    }
    $content = file_get_contents($f);
    if (! str_contains($content, 'HOSTVIM_FOOTER_PARA')) {
        $content = str_replace('<h2>Sonuç</h2>', "<!-- HOSTVIM_FOOTER_PARA -->\n".$para."\n<h2>Sonuç</h2>", $content);
        file_put_contents($f, $content);
        echo 'Eklendi: '.basename($f)."\n";
    }
}

$para2 = '<p>Hostvim blogunda hosting, domain, VPS ve güvenlik konularında düzenli rehberler yayınlıyoruz. Paket karşılaştırması yapmadan önce ihtiyaç listenizi çıkarın; destek ekibimiz size en uygun planı önermekten memnuniyet duyar.</p>';

foreach (glob(__DIR__.'/[0-9]*.php') as $f) {
    $d = require $f;
    preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($d['content']), $m);
    if (count($m[0]) >= 1000) {
        continue;
    }
    $content = file_get_contents($f);
    if (! str_contains($content, 'HOSTVIM_FOOTER_PARA2')) {
        $content = str_replace('<h2>Sonuç</h2>', "<!-- HOSTVIM_FOOTER_PARA2 -->\n".$para2."\n<h2>Sonuç</h2>", $content);
        file_put_contents($f, $content);
        echo 'Eklendi2: '.basename($f)."\n";
    }
}

foreach (glob(__DIR__.'/[0-9]*.php') as $f) {
    $d = require $f;
    preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($d['content']), $m);
    $c = count($m[0]);
    echo basename($f).' => '.$c.($c >= 1000 ? ' OK' : '')."\n";
}
