<?php

namespace Database\Seeders;

/**
 * Panelze landing — yasal sayfa tanımları.
 *
 * @return list<array{locale: string, slug: string, title: string, meta_description: string, sort_order: int, content: string}>
 */
final class LegalPagesDefinitions
{
    public static function all(): array
    {
        return array_merge(
            self::turkishPages(),
            self::englishPages(),
        );
    }

    /**
     * @return list<array{locale: string, slug: string, title: string, meta_description: string, sort_order: int, content: string}>
     */
    private static function turkishPages(): array
    {
        $b = PanelzeLegalContent::tr();

        return [
            self::row('tr', 'sss', 'Sık Sorulan Sorular', 'Panelze kurulum, lisans ve destek hakkında SSS.', 30, $b->sss),
            self::row('tr', 'kvkk', 'KVKK Aydınlatma Metni', '6698 sayılı KVKK kapsamında kişisel verilerin işlenmesine ilişkin aydınlatma.', 31, $b->kvkk),
            self::row('tr', 'gizlilik-politikasi', 'Gizlilik Politikası', 'Web sitesi ve hizmet kullanımında kişisel verilerin korunması.', 32, $b->gizlilik),
            self::row('tr', 'cerez-politikasi', 'Çerez Politikası', 'Çerez türleri, amaçları ve tercih yönetimi.', 33, $b->cerez),
            self::row('tr', 'mesafeli-satis', 'Mesafeli Satış Sözleşmesi', '6502 sayılı Kanun ve Mesafeli Sözleşmeler Yönetmeliği kapsamı.', 34, $b->mesafeli),
            self::row('tr', 'kullanim-kosullari', 'Kullanım Koşulları', 'Yazılım, web sitesi ve hizmetlerin kullanım şartları.', 35, $b->kullanim),
            self::row('tr', 'sla', 'Hizmet Seviyesi (SLA)', 'Erişilebilirlik hedefleri, bakım ve destek çerçevesi.', 36, $b->sla),
            self::row('tr', 'iade-ve-iptal', 'Ücret İadesi ve İptal Koşulları', 'Cayma, iptal ve iade süreçleri.', 37, $b->iade),
            self::row('tr', 'veri-merkezi', 'Veri Merkezi ve Altyapı', 'Barındırma lokasyonu ve alt işlemci bilgisi.', 38, $b->veri),
            self::row('tr', 'musteri-sozlesmesi', 'Müşteri Hizmet Sözleşmesi', 'Lisans / SaaS hosting paneli hizmet sözleşmesi çerçevesi.', 39, $b->musteri),
        ];
    }

    /**
     * @return list<array{locale: string, slug: string, title: string, meta_description: string, sort_order: int, content: string}>
     */
    private static function englishPages(): array
    {
        $b = PanelzeLegalContent::en();

        return [
            self::row('en', 'sss', 'Frequently asked questions', 'Panelze setup, licensing and support FAQ.', 30, $b->sss),
            self::row('en', 'kvkk', 'Privacy & data protection notice', 'How we process personal data in line with applicable law.', 31, $b->kvkk),
            self::row('en', 'gizlilik-politikasi', 'Privacy policy', 'How we collect, use, and protect personal data.', 32, $b->gizlilik),
            self::row('en', 'cerez-politikasi', 'Cookie policy', 'Cookies we use and how to manage preferences.', 33, $b->cerez),
            self::row('en', 'mesafeli-satis', 'Distance / online sales terms', 'Terms for online purchase of digital services or licenses.', 34, $b->mesafeli),
            self::row('en', 'kullanim-kosullari', 'Terms of service', 'Rules for using our website, software, and services.', 35, $b->kullanim),
            self::row('en', 'sla', 'Service level agreement (SLA)', 'Availability targets, maintenance, and support response goals.', 36, $b->sla),
            self::row('en', 'iade-ve-iptal', 'Refunds & cancellation', 'Cooling-off, cancellation, and refund rules.', 37, $b->iade),
            self::row('en', 'veri-merkezi', 'Data centre & infrastructure', 'Hosting location and subprocessors (summary).', 38, $b->veri),
            self::row('en', 'musteri-sozlesmesi', 'Customer agreement', 'Framework agreement for licensing / SaaS of the hosting control panel.', 39, $b->musteri),
        ];
    }

    /**
     * @param  array{locale: string, slug: string, title: string, meta_description: string, sort_order: int, content: string}  $row
     */
    private static function row(string $locale, string $slug, string $title, string $meta, int $sort, string $content): array
    {
        $date = date('Y-m-d');

        return [
            'locale' => $locale,
            'slug' => $slug,
            'title' => $title,
            'meta_description' => $meta,
            'sort_order' => $sort,
            'content' => str_replace('{DATE}', $date, $content),
        ];
    }
}
