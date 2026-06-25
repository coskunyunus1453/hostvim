<template>
    <footer class="bg-gradient-to-br from-pink-200 via-yellow-100 to-cyan-200 text-gray-800 mt-auto border-t-4 border-pink-400">
        <div class="container mx-auto px-4 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
                <!-- Hakkımızda -->
                <div>
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-12 h-12 bg-gradient-to-br from-pink-500 via-yellow-400 to-cyan-500 rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-material-lg border-4 border-pink-600">
                            🎈
                        </div>
                        <div class="text-2xl font-black bg-gradient-to-r from-pink-600 via-yellow-500 to-cyan-600 bg-clip-text text-transparent" style="font-family: 'Comic Sans MS', cursive;">
                            KODSAR
                        </div>
                    </div>
                    <p class="text-gray-700 mb-6 leading-relaxed font-bold" style="font-family: 'Comic Sans MS', cursive;">
                        Çocuk giyim ve ürünleri için renkli ve eğlenceli dünyamıza hoş geldiniz! 🎉
                    </p>
                    <div class="flex space-x-3">
                        <a href="#" class="w-12 h-12 bg-white rounded-full flex items-center justify-center hover:bg-pink-400 transition-all duration-300 transform hover:scale-125 hover:rotate-12 shadow-material-lg border-4 border-pink-300">
                            <span class="text-2xl">📘</span>
                        </a>
                        <a href="#" class="w-12 h-12 bg-white rounded-full flex items-center justify-center hover:bg-pink-400 transition-all duration-300 transform hover:scale-125 hover:rotate-12 shadow-material-lg border-4 border-pink-300">
                            <span class="text-2xl">📷</span>
                        </a>
                        <a href="#" class="w-12 h-12 bg-white rounded-full flex items-center justify-center hover:bg-cyan-400 transition-all duration-300 transform hover:scale-125 hover:rotate-12 shadow-material-lg border-4 border-cyan-300">
                            <span class="text-2xl">🐦</span>
                        </a>
                        <a href="#" class="w-12 h-12 bg-white rounded-full flex items-center justify-center hover:bg-yellow-400 transition-all duration-300 transform hover:scale-125 hover:rotate-12 shadow-material-lg border-4 border-yellow-300">
                            <span class="text-2xl">📺</span>
                        </a>
                    </div>
                </div>

                <!-- Hızlı Linkler -->
                <div>
                    <h4 class="text-lg font-black mb-6 text-pink-600" style="font-family: 'Comic Sans MS', cursive;">HIZLI LİNKLER 🎁</h4>
                    <ul class="space-y-3">
                        <li v-for="item in footerLinks" :key="item.id">
                            <Link
                                :href="menuHref(item)"
                                class="text-gray-700 hover:text-pink-600 transition-colors flex items-center space-x-2 group font-bold"
                                style="font-family: 'Comic Sans MS', cursive;"
                            >
                                <span class="text-xl opacity-0 group-hover:opacity-100 transform group-hover:translate-x-1 transition-all">👉</span>
                                <span>{{ item.title || item.label }}</span>
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Müşteri Hizmetleri -->
                <div>
                    <h4 class="text-lg font-black mb-6 text-cyan-600" style="font-family: 'Comic Sans MS', cursive;">MÜŞTERİ HİZMETLERİ 🛍️</h4>
                    <ul class="space-y-3">
                        <li><Link :href="route('account.orders.index')" class="text-gray-700 hover:text-pink-600 font-bold" style="font-family: 'Comic Sans MS', cursive;">Siparişlerim</Link></li>
                        <li><Link :href="route('account.downloads.index')" class="text-gray-700 hover:text-pink-600 font-bold" style="font-family: 'Comic Sans MS', cursive;">İndirmelerim</Link></li>
                        <li><Link :href="legalLink('kullanim-sartlari')" class="text-gray-700 hover:text-pink-600 font-bold" style="font-family: 'Comic Sans MS', cursive;">Lisans &amp; İade</Link></li>
                        <li><Link :href="legalLink('iletisim')" class="text-gray-700 hover:text-pink-600 font-bold" style="font-family: 'Comic Sans MS', cursive;">İletişim</Link></li>
                    </ul>
                </div>

                <!-- Yasal -->
                <div>
                    <h4 class="text-lg font-black mb-6 text-yellow-600" style="font-family: 'Comic Sans MS', cursive;">YASAL 📜</h4>
                    <ul class="space-y-3">
                        <li v-for="item in legalMenuItems" :key="'legal-' + item.id">
                            <Link :href="menuHref(item)" class="text-gray-700 hover:text-pink-600 font-bold" style="font-family: 'Comic Sans MS', cursive;">{{ item.label }}</Link>
                        </li>
                    </ul>
                </div>

                <!-- İletişim -->
                <div>
                    <h4 class="text-lg font-black mb-6 text-yellow-600" style="font-family: 'Comic Sans MS', cursive;">İLETİŞİM 📧</h4>
                    <ul class="space-y-4">
                        <li class="flex items-center space-x-3 text-gray-700">
                            <div class="w-12 h-12 bg-gradient-to-br from-pink-500 to-pink-600 rounded-full flex items-center justify-center border-4 border-pink-400 shadow-material-lg">
                                <span class="text-2xl">📧</span>
                            </div>
                            <a href="mailto:info@kodsar.com" class="hover:text-pink-600 transition-colors font-bold" style="font-family: 'Comic Sans MS', cursive;">info@kodsar.com</a>
                        </li>
                        <li class="flex items-center space-x-3 text-gray-700">
                            <div class="w-12 h-12 bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-full flex items-center justify-center border-4 border-cyan-400 shadow-material-lg">
                                <span class="text-2xl">📞</span>
                            </div>
                            <a href="tel:+905551234567" class="hover:text-pink-600 transition-colors font-bold" style="font-family: 'Comic Sans MS', cursive;">+90 (555) 123 45 67</a>
                        </li>
                        <li class="flex items-center space-x-3 text-gray-700">
                            <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-full flex items-center justify-center border-4 border-yellow-400 shadow-material-lg">
                                <span class="text-2xl">📍</span>
                            </div>
                            <span class="font-bold" style="font-family: 'Comic Sans MS', cursive;">İstanbul, Türkiye</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Alt Çizgi -->
            <div class="border-t-4 border-pink-400 mt-12 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-700 text-sm mb-4 md:mb-0 font-black" style="font-family: 'Comic Sans MS', cursive;">
                        © {{ new Date().getFullYear() }} KODSAR. Tüm hakları saklıdır. 🎈
                    </p>
                    <div class="flex flex-wrap gap-4 justify-center md:justify-end">
                        <Link :href="legalLink('gizlilik-politikasi')" class="text-gray-700 hover:text-pink-600 text-sm font-bold" style="font-family: 'Comic Sans MS', cursive;">Gizlilik Politikası</Link>
                        <Link :href="legalLink('kullanim-sartlari')" class="text-gray-700 hover:text-pink-600 text-sm font-bold" style="font-family: 'Comic Sans MS', cursive;">Kullanım Şartları</Link>
                        <Link :href="legalLink('kvkk')" class="text-gray-700 hover:text-pink-600 text-sm font-bold" style="font-family: 'Comic Sans MS', cursive;">KVKK</Link>
                        <Link :href="legalLink('hesap-silme')" class="text-gray-700 hover:text-pink-600 text-sm font-bold" style="font-family: 'Comic Sans MS', cursive;">Hesap Silme</Link>
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
    menus: { type: Object, default: () => ({}) },
});

const { footerMenuItems, legalMenuItems, menuHref, legalLink } = useFooterMenus(props.menus);

const footerLinks = computed(() => footerMenuItems.value.length ? footerMenuItems.value : [
    { id: 1, label: 'Ana Sayfa', href: route('home') },
    { id: 2, label: 'Hakkımızda', href: legalLink('hakkimizda') },
]);
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&display=swap');
</style>

