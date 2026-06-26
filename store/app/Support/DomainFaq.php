<?php

namespace App\Support;

/**
 * /domain sayfasinda gosterilen ve JSON-LD (FAQPage) yapisal verisini besleyen
 * Sikca Sorulan Sorular. HostVim'e ozgu, SEO odakli icerik.
 */
class DomainFaq
{
    /**
     * @return list<array{q: string, a: string}>
     */
    public static function all(): array
    {
        return [
            [
                'q' => 'Domain (alan adı) nedir?',
                'a' => 'Domain, yani alan adı, web sitenizin internetteki adresidir. Ziyaretçiler sitenize ulaşmak için sayısal IP adresi yerine, akılda kalıcı ve okunabilir olan alan adınızı (örneğin markaniz.com) yazarak erişir. Alan adı, markanızın dijital kimliğinin temel taşıdır.',
            ],
            [
                'q' => 'Domain tescili (kaydı) nasıl yapılır?',
                'a' => 'Bir alan adını tescil edebilmek için öncelikle o adın başkası tarafından alınmamış ve müsait olması gerekir. HostVim domain sayfasındaki arama kutusuna istediğiniz adı yazıp müsaitliğini anında sorgulayın. Müsaitse "Sepete Ekle" butonuna tıklayın ve ödeme adımlarını tamamlayarak tescil işlemini bitirin. Ödemeniz onaylandığında alan adınız adınıza kaydedilir.',
            ],
            [
                'q' => 'Dolu bir alan adının sahibini nasıl öğrenebilirim?',
                'a' => 'HostVim domain sorgulamasında, kayıtlı (dolu) bir alan adı için "Sahiplik bilgisi (WHOIS)" butonuna tıklayarak kayıt firmasını, kayıt ve bitiş tarihlerini, ad sunucularını ve durum bilgilerini görüntüleyebilirsiniz. Kişisel sahip bilgileri çoğu uzantıda gizlilik (GDPR/WHOIS privacy) nedeniyle gizlenir; bu durumda kayıt firması ve teknik bilgiler gösterilir.',
            ],
            [
                'q' => 'Domain transferi nasıl yapılır?',
                'a' => 'Domain transferi, bir alan adının yönetiminin başka bir kayıt firmasından HostVim\'e taşınmasıdır. Transfer için: WHOIS gizliliğini kaldırın, transfer kilidini açın ve mevcut firmanızdan transfer kodunu (EPP/Auth kodu) alın. Ardından alan adınızı HostVim\'de sorgulayıp transfer işlemini başlatın. Transfer ücretlidir ve çoğu uzantıda alan adınıza 1 yıl ek süre ekler. İşlemin sorunsuz tamamlanması için süre dolmasına en az 14 gün olmasına dikkat edin.',
            ],
            [
                'q' => 'Domain yenileme nasıl yapılır?',
                'a' => 'Alan adınızı kullanmaya devam edecekseniz, süresi dolmadan önce yenilemeniz gerekir. HostVim müşteri hesabınızda "Alan Adlarım" sayfasından ilgili domaini seçip yenileme adımını başlatabilir, kaç yıl yenilemek istediğinizi belirleyip ödemeyi tamamlayabilirsiniz. Hizmet kesintisi yaşamamak için yenilemeyi süre bitmeden birkaç gün önce yapmanızı öneririz.',
            ],
            [
                'q' => 'Domain fiyatları nasıl belirleniyor?',
                'a' => 'Domain fiyatları, kayıt operatörlerinin (registry) toptan fiyatları ve güncel döviz kuru baz alınarak belirlenir. Kampanyalı uzantılarda indirim genellikle yalnızca ilk yıl için geçerlidir; sonraki yıllarda yenileme fiyatı uygulanır. Tüm uzantıların kayıt, yenileme ve transfer fiyatlarını bu sayfadaki fiyat tablosundan şeffaf biçimde görebilirsiniz.',
            ],
            [
                'q' => 'Domain satın aldıktan sonra ne zaman aktif olur?',
                'a' => 'Kredi/banka kartıyla ödeme yaptığınızda siparişiniz anında onaylanır ve alan adınız hemen tescillenir. Havale/EFT ile ödemede ise dekontunuzun onaylanmasının ardından tescil tamamlanır. Bazı uzantılarda kayıt operatörü, iletişim bilgilerinizin doğrulanması için e-posta gönderebilir; bu durumda e-postadaki bağlantıyı onaylamanız gerekir.',
            ],
            [
                'q' => 'Domain tescili için belge gerekiyor mu?',
                'a' => 'Alan adları "ilk gelen alır" prensibiyle, çoğu uzantıda belgesiz olarak kaydedilir. BTK/TRABİS düzenlemesiyle .tr uzantılı alan adları da (istisnai uzantılar hariç) artık belgesiz tescil edilebilmektedir.',
            ],
            [
                'q' => 'Aldığım domaini iade edebilir miyim?',
                'a' => 'Domain tescili ve yenilemesi, kayıt operatörü nezdinde anında işleme alındığından iade edilemez. Bu nedenle satın alma öncesinde alan adınızı dikkatlice kontrol etmeniz ve onay adımlarını gözden geçirmeniz önemlidir.',
            ],
            [
                'q' => 'Domainimi başka bir hosting ile kullanabilir miyim?',
                'a' => 'Evet. HostVim\'den aldığınız alan adının ad sunucularını (NS) ve DNS kayıtlarını müşteri panelinizden dilediğiniz gibi yönetebilir, alan adınızı istediğiniz hosting sağlayıcısına yönlendirebilirsiniz. Alan adınız tamamen sizin kontrolünüzdedir.',
            ],
        ];
    }
}
