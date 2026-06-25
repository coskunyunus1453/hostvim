<template>
    <header class="bg-white shadow-material-md sticky top-0 z-50 backdrop-blur-sm bg-white/95">
        <!-- Üst Bar (İletişim, Dil, Para Birimi) -->
        <div class="bg-gradient-to-r from-primary-600 via-primary-500 to-secondary-600 text-white py-2 hidden md:block">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center text-sm">
                    <div class="flex items-center space-x-6">
                        <a href="tel:+905551234567" class="flex items-center space-x-2 hover:text-accent-300 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span>+90 (555) 123 45 67</span>
                        </a>
                        <a href="mailto:info@kodsar.com" class="flex items-center space-x-2 hover:text-accent-300 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span>info@kodsar.com</span>
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <select class="bg-transparent border-none text-white text-sm cursor-pointer hover:text-accent-300 transition-colors focus:outline-none">
                            <option>🇹🇷 TR</option>
                            <option>🇬🇧 EN</option>
                        </select>
                        <select class="bg-transparent border-none text-white text-sm cursor-pointer hover:text-accent-300 transition-colors focus:outline-none">
                            <option>₺ TRY</option>
                            <option>$ USD</option>
                            <option>€ EUR</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ana Header -->
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between py-4">
                <!-- Logo -->
                <Link :href="route('home')" class="flex items-center space-x-3 group">
                    <div v-if="$page.props.site?.logo?.url" class="flex items-center">
                        <img 
                            :src="$page.props.site.logo.url" 
                            alt="Logo" 
                            :style="{ 
                                width: $page.props.site.logo.width + 'px', 
                                height: $page.props.site.logo.height + 'px',
                                maxWidth: '200px',
                                objectFit: 'contain'
                            }"
                            class="transition-transform duration-300 group-hover:scale-105"
                        />
                    </div>
                    <div v-else class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-primary-600 via-primary-500 to-secondary-600 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-material group-hover:shadow-material-lg transition-all duration-300 transform group-hover:scale-110">
                            💻
                        </div>
                        <div class="hidden sm:block">
                            <div class="text-2xl font-bold bg-gradient-to-r from-primary-600 to-secondary-600 bg-clip-text text-transparent">
                                KODSAR
                            </div>
                            <div class="text-xs text-gray-500">Yazılım Satış Platformu</div>
                        </div>
                    </div>
                </Link>

                <!-- Arama Çubuğu (Desktop) -->
                <div class="hidden lg:flex flex-1 max-w-2xl mx-8">
                    <form @submit.prevent="handleSearch" class="w-full flex shadow-material rounded-full overflow-hidden">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Yazılım, lisans veya kategori ara..."
                            class="flex-1 px-6 py-3 border-0 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-gray-50"
                        />
                        <button
                            type="submit"
                            class="bg-gradient-to-r from-primary-600 to-primary-700 text-white px-8 py-3 hover:from-primary-700 hover:to-primary-800 transition-all duration-200 font-semibold"
                        >
                            <span class="flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <span>Ara</span>
                            </span>
                        </button>
                    </form>
                </div>

                <!-- Sağ Menü (Sepet, Kullanıcı) -->
                <div class="flex items-center space-x-3">
                    <!-- Sepet -->
                    <Link 
                        :href="route('cart.index')" 
                        class="relative p-3 rounded-full hover:bg-gray-100 transition-all duration-200 group"
                    >
                        <svg class="w-6 h-6 text-gray-700 group-hover:text-primary-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span
                            v-if="cartCount > 0"
                            class="absolute top-0 right-0 bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center font-bold shadow-material animate-pulse"
                        >
                            {{ cartCount }}
                        </span>
                    </Link>

                    <!-- Kullanıcı Menüsü -->
                    <div class="relative" v-if="$page.props.auth?.user">
                        <button
                            @click="showUserMenu = !showUserMenu"
                            class="flex items-center space-x-2 p-2 rounded-full hover:bg-gray-100 transition-all duration-200"
                        >
                            <div class="w-10 h-10 bg-gradient-to-br from-primary-600 to-secondary-600 rounded-full flex items-center justify-center text-white font-semibold shadow-material">
                                {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                            </div>
                            <span class="hidden md:block font-medium text-gray-700">{{ $page.props.auth.user.name }}</span>
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div
                            v-if="showUserMenu"
                            @click.away="showUserMenu = false"
                            class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-material-lg py-2 z-50 border border-gray-100"
                        >
                            <Link
                                :href="route('dashboard')"
                                class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-50 transition-colors"
                                @click="showUserMenu = false"
                            >
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span class="text-gray-700">Hesabım</span>
                            </Link>
                            <Link
                                :href="route('profile.edit')"
                                class="flex items-center space-x-3 px-4 py-3 hover:bg-gray-50 transition-colors"
                                @click="showUserMenu = false"
                            >
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="text-gray-700">Profil</span>
                            </Link>
                            <div class="border-t border-gray-100 my-2"></div>
                            <button
                                @click="logout"
                                type="button"
                                class="w-full flex items-center space-x-3 px-4 py-3 hover:bg-red-50 transition-colors text-red-600"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span>Çıkış Yap</span>
                            </button>
                        </div>
                    </div>
                    <div v-else class="flex items-center space-x-2">
                        <Link
                            :href="route('login')"
                            class="px-4 py-2 text-gray-700 hover:text-primary-600 font-medium transition-colors"
                        >
                            Giriş Yap
                        </Link>
                        <Link
                            :href="route('register')"
                            class="px-6 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-full font-semibold shadow-material hover:shadow-material-lg transform hover:scale-105 transition-all duration-200"
                        >
                            Kayıt Ol
                        </Link>
                    </div>
                </div>

                <!-- Mobil Menü Butonu -->
                <button
                    @click="showMobileMenu = !showMobileMenu"
                    class="lg:hidden p-2 rounded-full hover:bg-gray-100 transition-colors"
                >
                    <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <!-- Ana Navigasyon -->
            <nav class="hidden lg:flex border-t border-gray-100 py-4">
                <ul class="flex items-center space-x-1">
                    <li v-for="item in mainMenuItems" :key="item.id">
                        <Link
                            :href="item.href || item.url"
                            class="px-4 py-2 rounded-full text-gray-700 hover:bg-primary-50 hover:text-primary-600 font-medium transition-all duration-200 relative group"
                        >
                            {{ item.title || item.label }}
                            <span class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-0 h-0.5 bg-primary-600 group-hover:w-3/4 transition-all duration-300"></span>
                        </Link>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Mobil Menü -->
        <div
            v-if="showMobileMenu"
            class="lg:hidden border-t border-gray-100 bg-white shadow-material-lg"
        >
            <div class="container mx-auto px-4 py-4">
                <!-- Mobil Arama -->
                <form @submit.prevent="handleSearch" class="mb-4">
                    <div class="flex shadow-material rounded-full overflow-hidden">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Ara..."
                            class="flex-1 px-4 py-3 border-0 focus:outline-none focus:ring-2 focus:ring-primary-500 bg-gray-50"
                        />
                        <button
                            type="submit"
                            class="bg-gradient-to-r from-primary-600 to-primary-700 text-white px-6 py-3"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>
                    </div>
                </form>
                <!-- Mobil Navigasyon -->
                <ul class="space-y-1">
                    <li v-for="item in mainMenuItems" :key="item.id">
                        <Link
                            :href="item.href || item.url"
                            class="block px-4 py-3 rounded-xl text-gray-700 hover:bg-primary-50 hover:text-primary-600 font-medium transition-colors"
                            @click="showMobileMenu = false"
                        >
                            {{ item.title || item.label }}
                        </Link>
                    </li>
                </ul>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const props = defineProps({
    menus: {
        type: Object,
        default: () => ({}),
    },
    cartCount: {
        type: Number,
        default: 0,
    },
});

const searchQuery = ref('');
const showUserMenu = ref(false);
const showMobileMenu = ref(false);

const mainMenuItems = computed(() => {
    if (props.menus?.header_main?.items) {
        return props.menus.header_main.items;
    }
    return [
        { id: 1, label: 'Ana Sayfa', href: route('home') },
        { id: 2, label: 'Kategoriler', href: '#' },
        { id: 3, label: 'Kampanyalar', href: '#' },
        { id: 4, label: 'İletişim', href: '#' },
    ];
});

const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.visit(route('home'), {
            data: { search: searchQuery.value },
        });
    }
};

const logout = () => {
    router.post(route('logout'), {}, {
        onSuccess: () => {
            showUserMenu.value = false;
            router.visit(route('home'));
        },
        onError: (errors) => {
            console.error('Logout error:', errors);
        },
    });
};

// Click outside to close menu
const handleClickOutside = (event) => {
    if (showUserMenu.value && !event.target.closest('.relative')) {
        showUserMenu.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

