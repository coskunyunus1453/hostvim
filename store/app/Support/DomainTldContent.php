<?php

namespace App\Support;

/**
 * Populer alan adi uzantilari icin SEO odakli icerik ve kayit operatoru
 * (registry) bilgileri. /domain sayfasinda her uzanti icin bilgilendirici
 * bir bolum olarak gosterilir.
 *
 * Yalnizca burada tanimli VE sistemde aktif olan uzantilar icerikli gosterilir.
 *
 * Yapı: tld => [registry, address, title, paragraphs[]]
 */
class DomainTldContent
{
    /**
     * @return array<string, array{registry: string, address: string, title: string, paragraphs: list<string>}>
     */
    public static function all(): array
    {
        return [
            '.com' => [
                'registry' => 'VeriSign, Inc.',
                'address' => '12061 Bluemont Way, Reston, VA 20190, ABD',
                'title' => 'İnternetin en köklü adresi: .com',
                'paragraphs' => [
                    '.com uzantısı, bireysel kullanıcılardan küresel şirketlere kadar dünyanın her kesiminden milyonlarca site tarafından tercih edilen, internetin en tanınan ve en güvenilir alan adıdır. markaniz.com adresi, ziyaretçilerde güven ve profesyonellik algısını ilk saniyeden oluşturmanın en doğrudan yoludur.',
                    'On yılları aşan birikimi ve küresel tanınırlığıyla .com, SEO açısından en güçlü uzantı olma özelliğini korur. Markanızı sağlam temeller üzerine kurmak ve kullanıcı güvenini pekiştirmek istiyorsanız, .com adresinizi HostVim ile saniyeler içinde sorgulayıp kaydedebilirsiniz.',
                ],
            ],
            '.net' => [
                'registry' => 'VeriSign, Inc.',
                'address' => '12061 Bluemont Way, Reston, VA 20190, ABD',
                'title' => 'Teknoloji ve altyapının güvenilir adresi: .net',
                'paragraphs' => [
                    '"Network" kelimesinden türeyen .net; teknoloji şirketleri, internet servis sağlayıcıları, yazılım firmaları ve altyapı hizmetleri için en çok tercih edilen uzantılardandır. markaniz.net adresi teknolojiyle iç içe, güvenilir bir dijital kimlik oluşturur.',
                    'İnternetin en köklü uzantılarından biri olan .net, arama motorları tarafından .com ile eşdeğer güvenilirlikte değerlendirilir. İstediğiniz adı .com\'da bulamadıysanız .net, hem SEO hem kullanıcı güveni açısından güçlü bir alternatiftir.',
                ],
            ],
            '.org' => [
                'registry' => 'Public Interest Registry',
                'address' => '11911 Freedom Drive, Suite 1000, Reston, VA 20190, ABD',
                'title' => 'Kurumlar ve topluluklar için: .org',
                'paragraphs' => [
                    '.org uzantısı; dernekler, vakıflar, sivil toplum kuruluşları ve topluma fayda odaklı yapılar için internetin en güvenilir adreslerindendir. kurumunuz.org, ziyaretçilere şeffaf ve toplum yararına çalışan bir kimliği ilk andan hissettirir.',
                    'Köklü geçmişiyle .org, arama motorlarınca otoriter ve güvenilir içerikle ilişkilendirilir. Eğitimden sağlığa, çevreden uluslararası organizasyonlara kadar geniş bir kullanım alanına sahip .org\'u HostVim ile güvenle kaydedebilirsiniz.',
                ],
            ],
            '.tr' => [
                'registry' => 'Bilgi Teknolojileri ve İletişim Kurumu (BTK / TRABİS)',
                'address' => 'Eskişehir Yolu 10. Km No:276, Çankaya, Ankara 06530, Türkiye',
                'title' => 'Türkiye\'nin en kısa ve güçlü adresi: .tr',
                'paragraphs' => [
                    '.tr, Türkiye\'nin ülke kodlu birinci seviye uzantısıdır. Eskiden yalnızca .com.tr gibi ikinci seviye yapılarda mümkün olan yerli kayıtlar artık doğrudan ve çok daha kısa bir formatta yapılabiliyor. markaniz.tr; Almanya\'nın .de, Fransa\'nın .fr adresine benzer şekilde Türkiye\'ye özgü, sade ve güçlü bir kimlik sunar.',
                    'Görece yeni olması .tr\'yi büyük bir fırsat penceresine dönüştürür: .com.tr\'de çoktan alınmış pek çok ad .tr\'de hâlâ müsait olabilir. Yerel SEO\'da belirgin avantaj sağlayan bu uzantıyı markanız için bir an önce HostVim üzerinden sahiplenmenizi öneririz.',
                ],
            ],
            '.io' => [
                'registry' => 'Identity Digital (Internet Computer Bureau)',
                'address' => '10500 NE 8th Street, Suite 750, Bellevue, WA 98004, ABD',
                'title' => 'Girişimlerin gözdesi: .io',
                'paragraphs' => [
                    '.io uzantısı; teknoloji girişimleri, SaaS platformları ve geliştiriciler arasında neredeyse standart hâline gelmiş modern bir adrestir. Kısa ve akılda kalıcı yapısı "input/output" çağrışımıyla teknolojiyle güçlü bir bağ kurar.',
                    'Startup ekosisteminde güçlü bir konum edinmek, ürününüzü çağdaş ve teknik bir kimlikle sunmak istiyorsanız markaniz.io ideal bir tercihtir. Müsaitliğini HostVim ile anında kontrol edebilirsiniz.',
                ],
            ],
            '.ai' => [
                'registry' => 'Government of Anguilla',
                'address' => 'The Valley, Anguilla',
                'title' => 'Yapay zekânın adresi: .ai',
                'paragraphs' => [
                    '.ai uzantısı; yapay zekâ, makine öğrenmesi ve veri odaklı projeler için son yılların en çok aranan adresidir. markaniz.ai, ürününüzün yenilikçi ve teknoloji odaklı vizyonunu daha ilk bakışta ortaya koyar.',
                    'Yapay zekâ alanında konumlanmak isteyen girişimler ve markalar için .ai, güçlü bir bilinirlik ve farklılaşma aracıdır. İstediğiniz .ai adresinin müsaitliğini HostVim üzerinden hemen sorgulayabilirsiniz.',
                ],
            ],
            '.dev' => [
                'registry' => 'Charleston Road Registry Inc. (Google)',
                'address' => '1600 Amphitheatre Parkway, Mountain View, CA 94043, ABD',
                'title' => 'Geliştiriciler için güvenli adres: .dev',
                'paragraphs' => [
                    '.dev uzantısı; yazılım geliştiriciler, ekipler ve teknoloji projeleri için tasarlanmış modern bir adrestir. Portföyünüzden dokümantasyonunuza kadar teknik kimliğinizi net biçimde yansıtır.',
                    '.dev, HSTS preload listesinde yer aldığından tüm sitelerde SSL sertifikası zorunludur; yani ziyaretçileriniz size her zaman şifreli bağlantıyla ulaşır. Güvenli ve teknik bir adres için markaniz.dev tercihini HostVim ile değerlendirin.',
                ],
            ],
            '.app' => [
                'registry' => 'Charleston Road Registry Inc. (Google)',
                'address' => '1600 Amphitheatre Parkway, Mountain View, CA 94043, ABD',
                'title' => 'Uygulamanızın vitrini: .app',
                'paragraphs' => [
                    '.app uzantısı; mobil uygulama geliştiricileri, SaaS platformları ve uygulama tabanlı girişimler için idealdir. uygulamaniz.app, projenizin ne olduğunu açıklamaya gerek kalmadan ilk bakışta anlatır.',
                    'Güvenlik konusunda öne çıkan .app, HSTS preload sayesinde zorunlu SSL ile gelir; bu da hem kullanıcı güvenine hem arama sıralamasına katkı sağlar. Akılda kalıcı ve güvenli bir adres için HostVim\'i kullanın.',
                ],
            ],
            '.co' => [
                'registry' => '.CO Internet S.A.S.',
                'address' => 'Bogotá D.C., Kolombiya',
                'title' => 'Modern ve global: .co',
                'paragraphs' => [
                    '"company" ve "commerce" kelimelerinin evrensel kısaltması olan .co; girişimler, teknoloji şirketleri ve global markalar için kısa, akılda kalıcı ve modern bir adres sunar.',
                    '.com\'a yakın yapısı ve küresel tanınırlığı sayesinde startup dünyasında yaygın biçimde benimsenir. İstediğiniz adı .com\'da bulamadıysanız ya da daha minimalist bir görünüm istiyorsanız markaniz.co güçlü bir alternatiftir.',
                ],
            ],
            '.me' => [
                'registry' => 'doMEn / Karadağ Hükümeti',
                'address' => 'Rimski trg 46, Podgorica 81000, Karadağ',
                'title' => 'Kişisel markanın adresi: .me',
                'paragraphs' => [
                    'İngilizce\'de "ben" anlamına gelen .me; bloggerlar, freelancerlar, içerik üreticileri ve kişisel marka oluşturmak isteyenler için idealdir. isminiz.me, dijitaldeki kişisel kimliğinizi en sade biçimde ifade eder.',
                    'Kişisel portföy, özgeçmiş ve blog siteleri için en çok tercih edilen uzantılardan olan .me, kısa ve evrensel yapısıyla akılda kalır. Kendinizi en iyi anlatan adresi HostVim ile sahiplenin.',
                ],
            ],
            '.xyz' => [
                'registry' => 'XYZ.COM LLC',
                'address' => '4425 Spring Mountain Rd., Suite 2, Las Vegas, NV 89102, ABD',
                'title' => 'Özgür ve özgün: .xyz',
                'paragraphs' => [
                    '.xyz uzantısı; girişimciler, yaratıcı projeler ve geleneksel uzantıların dışında özgün bir kimlik isteyen herkes için modern bir tercihtir. Sıradışı yapısıyla yenilikçi bir vizyonu hissettirir.',
                    'Startup ekosisteminde hızla benimsenen .xyz, kısa yapısı ve uygun fiyatıyla öne çıkar. Yeni bir girişim ya da yaratıcı bir proje için markaniz.xyz adresini HostVim ile hemen sorgulayın.',
                ],
            ],
            '.online' => [
                'registry' => 'Radix Technologies Inc.',
                'address' => 'Grand Cayman, Cayman Adaları',
                'title' => 'Her proje için evrensel: .online',
                'paragraphs' => [
                    '.online uzantısı; e-ticaret sitelerinden dijital hizmet platformlarına kadar her türlü işletme için esnek ve geniş kapsamlı bir adrestir. markaniz.online, dijital dünyada aktif ve erişilebilir olduğunuzu doğrudan hissettirir.',
                    '"online" kelimesinin yüksek arama hacmiyle organik uyum sağlayan bu uzantı, SEO\'ya katkı sunar. Fiziksel işletmenizi dijitale taşımak ya da yeni bir girişim kurmak için HostVim ile değerlendirin.',
                ],
            ],
            '.site' => [
                'registry' => 'Radix Technologies Inc.',
                'address' => 'Grand Cayman, Cayman Adaları',
                'title' => 'Sade ve evrensel: .site',
                'paragraphs' => [
                    '.site uzantısı; bireysel projelerden kurumsal sitelere, bloglardan e-ticarete kadar her türlü dijital varlık için uygundur. Sektörden bağımsız, sade ve akılda kalıcı bir kimlik sunar.',
                    'İstediğiniz adı yaygın uzantılarda bulamadıysanız ya da hızlıca özgün bir adres oluşturmak istiyorsanız .site pratik ve maliyet etkin bir tercihtir. HostVim ile saniyeler içinde kaydedin.',
                ],
            ],
            '.store' => [
                'registry' => 'Radix Technologies Inc.',
                'address' => 'Grand Cayman, Cayman Adaları',
                'title' => 'E-ticaretin güçlü adresi: .store',
                'paragraphs' => [
                    '.store uzantısı; online mağazalar, perakende markaları ve dijital satış kanalları için güçlü bir adrestir. markaniz.store, sitenizin bir alışveriş platformu olduğunu ilk bakışta iletir.',
                    '"store" kelimesinin yüksek arama hacmiyle organik uyum sağlayan bu uzantı, e-ticaret SEO\'sunda rakiplerinizden ayrışmanızı sağlar. Online mağazanız için ideal adresi HostVim ile sorgulayın.',
                ],
            ],
            '.shop' => [
                'registry' => 'GMO Registry, Inc.',
                'address' => 'Shibuya-ku, Tokyo 150-8512, Japonya',
                'title' => 'Online mağazanız için: .shop',
                'paragraphs' => [
                    '.shop uzantısı; e-ticaret siteleri, butik markalar ve dijital satış kanalı oluşturmak isteyen işletmeler için sektöre özgü bir adrestir. Ziyaretçilere alışveriş yapabileceklerini net biçimde iletir.',
                    '"shop" kelimesinin yüksek arama hacmiyle uyumlu .shop, e-ticaret odaklı SEO\'da güçlü avantaj sunar. Yerel butikten global markaya kadar herkes için ideal olan bu adresi HostVim\'de inceleyin.',
                ],
            ],
            '.tech' => [
                'registry' => 'Radix Technologies Inc.',
                'address' => 'Grand Cayman, Cayman Adaları',
                'title' => 'Teknoloji markaları için: .tech',
                'paragraphs' => [
                    '.tech uzantısı; teknoloji şirketleri, startuplar, etkinlikler ve eğitim platformları için net bir sektörel kimlik sunar. markaniz.tech, teknolojiyle uğraştığınızı adıyla anlatır.',
                    'Teknoloji odaklı arama sorgularıyla organik uyum sağlayan .tech, sektörünüzde fark edilmenize yardımcı olur. İnovasyon odaklı markanız için HostVim ile bu adresi değerlendirin.',
                ],
            ],
            '.cloud' => [
                'registry' => 'Aruba S.p.A.',
                'address' => 'Ponte San Pietro, İtalya',
                'title' => 'Bulut çağının adresi: .cloud',
                'paragraphs' => [
                    '.cloud uzantısı; bulut hizmetleri, SaaS ürünleri, hosting ve altyapı firmaları için modern bir adrestir. markaniz.cloud, ürününüzün bulut tabanlı yapısını doğrudan vurgular.',
                    'Bulut teknolojilerinin hızla büyüdüğü bir dönemde .cloud, sektörel kimliğinizi güçlendirir. Teknoloji ve altyapı odaklı projeniz için HostVim ile müsaitliği kontrol edin.',
                ],
            ],
            '.info' => [
                'registry' => 'Identity Digital Limited',
                'address' => '10500 NE 8th Street, Suite 750, Bellevue, WA 98004, ABD',
                'title' => 'Bilgi odaklı projeler için: .info',
                'paragraphs' => [
                    '.info uzantısı; haber siteleri, ansiklopedik içerik platformları ve bilgi paylaşımını ön plana çıkaran projeler için köklü bir adrestir. Sitenizin bilgi odaklı ve şeffaf yapısını hissettirir.',
                    'İnternetin en tanınan uzantılarından biri olan .info, içerik odaklı sitelerle ilişkilendirilen güvenilir bir tercihtir. Kapsamlı bilgi sunan bir platform için HostVim ile kaydedin.',
                ],
            ],
            '.biz' => [
                'registry' => 'Registry Services, LLC',
                'address' => '100 S. Mill Ave, Suite 1600, Tempe, AZ 85281, ABD',
                'title' => 'İşletmeniz için: .biz',
                'paragraphs' => [
                    '"business" kelimesinden türeyen .biz; küçük ve orta ölçekli işletmeler ile girişimciler için tasarlanmış köklü bir adrestir. Sitenizin ticari amacını ilk andan açıkça iletir.',
                    '.com\'da uygun ad bulmakta zorlanan işletmeler için güvenilir bir alternatif olan .biz, ticari içerikle ilişkilendirilir. Markanızı özgün bir adresle sahiplenmek için HostVim\'i kullanın.',
                ],
            ],
            '.blog' => [
                'registry' => 'Knock Knock WHOIS There, LLC (Automattic)',
                'address' => '60 29th Street #343, San Francisco, CA 94110, ABD',
                'title' => 'İçerik üreticileri için: .blog',
                'paragraphs' => [
                    '.blog uzantısı; bireysel bloggerlar, içerik üreticileri ve blog tabanlı strateji yürüten markalar için odaklı bir adrestir. isminiz.blog, sitenizin ne sunduğunu adıyla anlatır.',
                    'İçerik odaklı yapısıyla "blog" anahtar kelimesinde doğal uyum sağlayan bu uzantı SEO\'ya organik katkı verir. Kişisel ya da kurumsal blogunuz için HostVim ile sorgulayın.',
                ],
            ],
            '.live' => [
                'registry' => 'Identity Digital (Dog Beach, LLC)',
                'address' => '10500 NE 8th Street, Suite 750, Bellevue, WA 98004, ABD',
                'title' => 'Canlı deneyimler için: .live',
                'paragraphs' => [
                    '.live uzantısı; canlı yayın platformları, etkinlik organizatörleri ve gerçek zamanlı içerik üreten yapılar için dinamik bir adrestir. Deneyiminizin canlı ve etkileşimli yapısını vurgular.',
                    'Canlı yayın tüketiminin hızla büyüdüğü bir dönemde .live, ilgili arama terimleriyle organik uyum sağlar. Etkinlik ya da yayın markanız için HostVim ile bu adresi değerlendirin.',
                ],
            ],
        ];
    }
}
