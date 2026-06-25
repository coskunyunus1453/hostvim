<template>
    <footer class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white mt-auto">
        <div class="container mx-auto px-4 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
                <!-- Hakkımızda -->
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-primary-600 via-primary-500 to-secondary-600 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-material">
                            💻
                        </div>
                        <div class="text-2xl font-bold bg-gradient-to-r from-primary-400 to-secondary-400 bg-clip-text text-transparent">
                            KODSAR
                        </div>
                    </div>
                    <p class="text-gray-400 mb-6 leading-relaxed">
                        Yazılım satış platformu. Lisanslı yazılımlar, dijital ürünler ve profesyonel çözümler için güvenilir adresiniz.
                    </p>
                    <div class="flex space-x-3">
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-primary-600 transition-all duration-300 transform hover:scale-110 shadow-material">
                            <span class="text-lg">📘</span>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-pink-600 transition-all duration-300 transform hover:scale-110 shadow-material">
                            <span class="text-lg">📷</span>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-blue-400 transition-all duration-300 transform hover:scale-110 shadow-material">
                            <span class="text-lg">🐦</span>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-red-600 transition-all duration-300 transform hover:scale-110 shadow-material">
                            <span class="text-lg">📺</span>
                        </a>
                    </div>
                </div>

                <!-- Hızlı Linkler -->
                <div>
                    <h4 class="text-lg font-bold mb-6 text-white">Hızlı Linkler</h4>
                    <ul class="space-y-3">
                        <li v-for="item in footerLinks" :key="item.id">
                            <Link
                                :href="menuHref(item)"
                                class="text-gray-400 hover:text-primary-400 transition-colors flex items-center space-x-2 group"
                            >
                                <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transform group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                <span>{{ item.title || item.label }}</span>
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Müşteri Hizmetleri -->
                <div>
                    <h4 class="text-lg font-bold mb-6 text-white">Müşteri Hizmetleri</h4>
                    <ul class="space-y-3">
                        <li>
                            <Link :href="route('account.orders.index')" class="text-gray-400 hover:text-primary-400 transition-colors flex items-center space-x-2 group">
                                <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transform group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                <span>Siparişlerim</span>
                            </Link>
                        </li>
                        <li>
                            <Link :href="route('account.downloads.index')" class="text-gray-400 hover:text-primary-400 transition-colors flex items-center space-x-2 group">
                                <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transform group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                <span>İndirmelerim</span>
                            </Link>
                        </li>
                        <li>
                            <Link :href="legalLink('kullanim-sartlari')" class="text-gray-400 hover:text-primary-400 transition-colors flex items-center space-x-2 group">
                                <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transform group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                <span>Lisans &amp; İade</span>
                            </Link>
                        </li>
                        <li>
                            <Link :href="legalLink('iletisim')" class="text-gray-400 hover:text-primary-400 transition-colors flex items-center space-x-2 group">
                                <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transform group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                <span>İletişim</span>
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Yasal -->
                <div>
                    <h4 class="text-lg font-bold mb-6 text-white">Yasal</h4>
                    <ul class="space-y-3">
                        <li v-for="item in legalMenuItems" :key="'legal-' + item.id">
                            <Link :href="menuHref(item)" class="text-gray-400 hover:text-primary-400 transition-colors flex items-center space-x-2 group">
                                <svg class="w-4 h-4 opacity-0 group-hover:opacity-100 transform group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                <span>{{ item.label }}</span>
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- İletişim -->
                <div>
                    <h4 class="text-lg font-bold mb-6 text-white">İletişim</h4>
                    <ul class="space-y-4">
                        <li class="flex items-center space-x-3 text-gray-400">
                            <div class="w-10 h-10 bg-primary-600 rounded-full flex items-center justify-center shadow-material">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <a href="mailto:info@kodsar.com" class="hover:text-primary-400 transition-colors">info@kodsar.com</a>
                        </li>
                        <li class="flex items-center space-x-3 text-gray-400">
                            <div class="w-10 h-10 bg-secondary-600 rounded-full flex items-center justify-center shadow-material">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <a href="tel:+905551234567" class="hover:text-primary-400 transition-colors">+90 (555) 123 45 67</a>
                        </li>
                        <li class="flex items-center space-x-3 text-gray-400">
                            <div class="w-10 h-10 bg-accent-600 rounded-full flex items-center justify-center shadow-material">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <span>İstanbul, Türkiye</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Alt Çizgi -->
            <div class="border-t border-gray-700 mt-12 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-400 text-sm mb-4 md:mb-0">
                        © {{ new Date().getFullYear() }} KODSAR. Tüm hakları saklıdır.
                    </p>
                    <div class="flex flex-wrap gap-4 justify-center md:justify-end">
                        <Link :href="legalLink('gizlilik-politikasi')" class="text-gray-400 hover:text-primary-400 text-sm transition-colors">Gizlilik Politikası</Link>
                        <Link :href="legalLink('kullanim-sartlari')" class="text-gray-400 hover:text-primary-400 text-sm transition-colors">Kullanım Şartları</Link>
                        <Link :href="legalLink('kvkk')" class="text-gray-400 hover:text-primary-400 text-sm transition-colors">KVKK</Link>
                        <Link :href="legalLink('hesap-silme')" class="text-gray-400 hover:text-primary-400 text-sm transition-colors">Hesap Silme</Link>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { useFooterMenus } from '@/Composables/useKodsarMenus';

const props = defineProps({
    menus: {
        type: Object,
        default: () => ({}),
    },
});

const { footerMenuItems, legalMenuItems, menuHref, legalLink } = useFooterMenus(props.menus);

const footerLinks = computed(() => {
    if (footerMenuItems.value.length) {
        return footerMenuItems.value;
    }
    return [
        { id: 1, label: 'Ana Sayfa', href: route('home') },
        { id: 2, label: 'Hakkımızda', href: legalLink('hakkimizda') },
        { id: 3, label: 'İletişim', href: legalLink('iletisim') },
    ];
});
</script>

