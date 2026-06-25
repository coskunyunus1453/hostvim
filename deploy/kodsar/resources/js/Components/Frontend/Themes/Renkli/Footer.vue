<template>
    <footer class="footer-main" :style="footerStyles">
        <!-- Newsletter -->
        <div class="newsletter-section py-12" :style="newsletterStyles">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto text-center">
                    <h3 class="text-3xl font-bold mb-4 text-white">Bültenimize Abone Olun!</h3>
                    <p class="text-white opacity-90 mb-6">Kampanyalardan ve özel fırsatlardan ilk siz haberdar olun</p>
                    <form @submit.prevent="handleNewsletter" class="flex flex-col sm:flex-row gap-4 max-w-md mx-auto">
                        <input
                            v-model="newsletterEmail"
                            type="email"
                            placeholder="E-posta adresiniz"
                            required
                            class="flex-1 px-6 py-4 rounded-xl border-0 focus:outline-none focus:ring-4 transition-all"
                            :style="newsletterInputStyles"
                        />
                        <button
                            type="submit"
                            class="px-8 py-4 rounded-xl font-bold transition-all duration-300 hover:scale-105 transform"
                            :style="newsletterButtonStyles"
                        >
                            Abone Ol
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Ana Footer İçerik -->
        <div class="footer-content py-16" :style="footerContentStyles">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
                    <!-- Hakkımızda -->
                    <div class="footer-column">
                        <h4 class="text-xl font-bold mb-6" :style="{ color: themeSettings?.primary_color || '#FF6B6B' }">
                            Hakkımızda
                        </h4>
                        <p class="text-sm opacity-80 mb-4 leading-relaxed">
                            Kodsar; mobil uygulama kaynak kodları, yazılım lisansları ve dijital ürünler sunan teknoloji platformudur. Güvenli ödeme, hızlı teslimat ve teknik destek.
                        </p>
                        <div class="flex space-x-4">
                            <a href="#" class="social-icon" :style="socialIconStyles">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </a>
                            <a href="#" class="social-icon" :style="socialIconStyles">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                </svg>
                            </a>
                            <a href="#" class="social-icon" :style="socialIconStyles">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                            </a>
                            <a href="#" class="social-icon" :style="socialIconStyles">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Hızlı Linkler -->
                    <div class="footer-column">
                        <h4 class="text-xl font-bold mb-6" :style="{ color: themeSettings?.primary_color || '#FF6B6B' }">
                            Hızlı Linkler
                        </h4>
                        <ul class="space-y-3">
                            <li v-for="(item, index) in footerMenuItems" :key="'quick-' + index">
                                <Link
                                    :href="item.href || item.url || '#'"
                                    class="text-sm opacity-80 hover:opacity-100 transition-all duration-300 hover:translate-x-2 inline-block"
                                >
                                    {{ item.label }}
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Yasal -->
                    <div class="footer-column">
                        <h4 class="text-xl font-bold mb-6" :style="{ color: themeSettings?.primary_color || '#FF6B6B' }">
                            Yasal
                        </h4>
                        <ul class="space-y-3">
                            <li v-for="(item, index) in legalMenuItems" :key="'legal-' + index">
                                <Link
                                    :href="item.href || item.url || '#'"
                                    class="text-sm opacity-80 hover:opacity-100 transition-all duration-300 hover:translate-x-2 inline-block"
                                >
                                    {{ item.label }}
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Müşteri Hizmetleri -->
                    <div class="footer-column">
                        <h4 class="text-xl font-bold mb-6" :style="{ color: themeSettings?.primary_color || '#FF6B6B' }">
                            Müşteri Hizmetleri
                        </h4>
                        <ul class="space-y-3">
                            <li>
                                <Link :href="route('account.orders.index')" class="text-sm opacity-80 hover:opacity-100 transition-all duration-300 hover:translate-x-2 inline-block">
                                    Siparişlerim
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('account.addresses.index')" class="text-sm opacity-80 hover:opacity-100 transition-all duration-300 hover:translate-x-2 inline-block">
                                    Adreslerim
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('account.downloads.index')" class="text-sm opacity-80 hover:opacity-100 transition-all duration-300 hover:translate-x-2 inline-block">
                                    İndirmelerim
                                </Link>
                            </li>
                            <li>
                                <Link :href="legalLink('kullanim-sartlari')" class="text-sm opacity-80 hover:opacity-100 transition-all duration-300 hover:translate-x-2 inline-block">
                                    İade ve Lisans
                                </Link>
                            </li>
                            <li>
                                <Link :href="legalLink('gizlilik-politikasi')" class="text-sm opacity-80 hover:opacity-100 transition-all duration-300 hover:translate-x-2 inline-block">
                                    Gizlilik
                                </Link>
                            </li>
                            <li>
                                <Link :href="legalLink('iletisim')" class="text-sm opacity-80 hover:opacity-100 transition-all duration-300 hover:translate-x-2 inline-block">
                                    İletişim
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- İletişim -->
                    <div class="footer-column">
                        <h4 class="text-xl font-bold mb-6" :style="{ color: themeSettings?.primary_color || '#FF6B6B' }">
                            İletişim
                        </h4>
                        <ul class="space-y-4">
                            <li class="flex items-start space-x-3">
                                <svg class="w-5 h-5 mt-1 flex-shrink-0" :style="{ color: themeSettings?.accent_color || '#FFE66D' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="text-sm opacity-80">İstanbul, Türkiye</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <svg class="w-5 h-5 mt-1 flex-shrink-0" :style="{ color: themeSettings?.accent_color || '#FFE66D' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span class="text-sm opacity-80">+90 (555) 123 45 67</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <svg class="w-5 h-5 mt-1 flex-shrink-0" :style="{ color: themeSettings?.accent_color || '#FFE66D' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span class="text-sm opacity-80">info@kodsar.com</span>
                            </li>
                            <li class="flex items-start space-x-3">
                                <svg class="w-5 h-5 mt-1 flex-shrink-0" :style="{ color: themeSettings?.accent_color || '#FFE66D' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-sm opacity-80">Pazartesi - Cuma: 09:00 - 18:00</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alt Footer -->
        <div class="footer-bottom py-6 border-t" :style="footerBottomStyles">
            <div class="container mx-auto px-4">
                <div class="flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
                    <div class="text-sm opacity-80">
                        © {{ new Date().getFullYear() }} Kodsar. Tüm hakları saklıdır.
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-4 md:gap-6">
                        <Link :href="legalLink('gizlilik-politikasi')" class="text-sm opacity-80 hover:opacity-100 transition-all">Gizlilik Politikası</Link>
                        <Link :href="legalLink('kullanim-sartlari')" class="text-sm opacity-80 hover:opacity-100 transition-all">Kullanım Şartları</Link>
                        <Link :href="legalLink('kvkk')" class="text-sm opacity-80 hover:opacity-100 transition-all">KVKK</Link>
                        <Link :href="legalLink('hesap-silme')" class="text-sm opacity-80 hover:opacity-100 transition-all">Hesap Silme</Link>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useFooterMenus } from '@/Composables/useKodsarMenus';

const props = defineProps({
    menus: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const newsletterEmail = ref('');
const { footerMenuItems, legalMenuItems, legalLink } = useFooterMenus(props.menus);

// Tema ayarları
const themeSettings = computed(() => page.props.theme?.settings || {});

// Footer menü öğeleri — useFooterMenus composable kullanılıyor

// Footer stilleri
const footerStyles = computed(() => {
    return {
        fontFamily: themeSettings.value.font_family || 'Poppins, sans-serif',
    };
});

const newsletterStyles = computed(() => {
    return {
        background: `linear-gradient(135deg, ${themeSettings.value.primary_color || '#FF6B6B'} 0%, ${themeSettings.value.secondary_color || '#4ECDC4'} 100%)`,
    };
});

const newsletterInputStyles = computed(() => {
    return {
        background: 'rgba(255, 255, 255, 0.95)',
        color: '#1f2937',
    };
});

const newsletterButtonStyles = computed(() => {
    return {
        background: themeSettings.value.accent_color || '#FFE66D',
        color: '#1f2937',
    };
});

const footerContentStyles = computed(() => {
    return {
        background: 'linear-gradient(180deg, #1f2937 0%, #111827 100%)',
        color: '#ffffff',
    };
});

const socialIconStyles = computed(() => {
    return {
        color: themeSettings.value.accent_color || '#FFE66D',
    };
});

const footerBottomStyles = computed(() => {
    return {
        background: '#111827',
        color: '#ffffff',
        borderColor: themeSettings.value.accent_color || '#FFE66D',
    };
});

const handleNewsletter = () => {
    // Newsletter kayıt işlemi
    if (newsletterEmail.value) {
        router.post(route('newsletter.subscribe'), {
            email: newsletterEmail.value,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                newsletterEmail.value = '';
                alert('Bültenimize başarıyla abone oldunuz!');
            },
        });
    }
};
</script>

<style scoped>
.footer-main {
    position: relative;
    overflow: hidden;
}

.footer-main::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.1;
    pointer-events: none;
}

.social-icon {
    transition: all 0.3s ease;
}

.social-icon:hover {
    transform: scale(1.2) rotate(5deg);
}
</style>



