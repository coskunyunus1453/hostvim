<template>
    <header 
        class="sticky top-0 z-50 transition-all duration-500 shadow-lg"
        :class="headerClasses"
        :style="headerStyles"
    >
        <!-- Flash Banner (Üstte) -->
        <div 
            v-if="themeSettings?.flash_enabled && flashBanner"
            class="flash-banner py-2 px-4 text-center text-white text-sm font-semibold animate-pulse"
            :style="flashStyles"
        >
            <div class="container mx-auto flex items-center justify-center space-x-2">
                <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span v-html="flashBanner"></span>
            </div>
        </div>

        <!-- Üst Bar -->
        <div class="top-bar hidden md:block py-2" :style="topBarStyles">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center text-sm">
                    <div class="flex items-center space-x-6">
                        <a href="tel:+905551234567" class="flex items-center space-x-2 hover:opacity-80 transition-all group">
                            <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span class="font-medium">+90 (555) 123 45 67</span>
                        </a>
                        <a href="mailto:info@kodsar.com" class="flex items-center space-x-2 hover:opacity-80 transition-all group">
                            <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span class="font-medium">info@kodsar.com</span>
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <select class="bg-transparent border-none text-white text-sm cursor-pointer hover:opacity-80 transition-all focus:outline-none font-medium">
                            <option>🇹🇷 TR</option>
                            <option>🇬🇧 EN</option>
                        </select>
                        <span class="opacity-60">|</span>
                        <select class="bg-transparent border-none text-white text-sm cursor-pointer hover:opacity-80 transition-all focus:outline-none font-medium">
                            <option>₺ TRY</option>
                            <option>$ USD</option>
                            <option>€ EUR</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ana Header -->
        <div class="main-header" :style="mainHeaderStyles">
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between py-4 gap-4">
                    <!-- Logo -->
                    <Link :href="route('home')" class="flex items-center space-x-3 group flex-shrink-0">
                        <div v-if="$page.props.site?.logo?.url" class="flex items-center">
                            <img 
                                :src="$page.props.site.logo.url" 
                                alt="Logo" 
                                class="h-12 w-auto transition-transform duration-300 group-hover:scale-110"
                            />
                        </div>
                        <div v-else class="flex items-center space-x-3">
                            <div 
                                class="logo-box w-14 h-14 rounded-2xl flex items-center justify-center text-white font-bold text-2xl shadow-xl group-hover:shadow-2xl transition-all duration-300 transform group-hover:scale-110 group-hover:rotate-12"
                                :style="logoStyles"
                            >
                                K
                            </div>
                            <div class="hidden sm:block">
                                <div 
                                    class="text-2xl font-bold"
                                    :style="logoTextStyles"
                                >
                                    KODSAR
                                </div>
                                <div class="text-xs opacity-80 font-medium">E-Ticaret Platformu</div>
                            </div>
                        </div>
                    </Link>

                    <!-- Arama Çubuğu (Desktop) -->
                    <div class="hidden lg:flex flex-1 max-w-3xl mx-4">
                        <form @submit.prevent="handleSearch" class="w-full flex shadow-2xl rounded-2xl overflow-hidden border-2 transition-all duration-300 hover:shadow-3xl" :style="searchBoxStyles">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Ürün, kategori veya marka ara..."
                                class="flex-1 px-6 py-4 border-0 focus:outline-none text-gray-700 placeholder-gray-400 bg-white focus:bg-gray-50 transition-colors"
                            />
                            <button
                                type="submit"
                                class="px-8 py-4 font-semibold flex items-center space-x-2 transition-all duration-300 transform hover:scale-105"
                                :style="searchButtonStyles"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <span>Ara</span>
                            </button>
                        </form>
                    </div>

                    <!-- Sağ Menü -->
                    <div class="flex items-center space-x-3 flex-shrink-0">
                        <!-- Favoriler -->
                        <Link 
                            v-if="$page.props.auth?.user"
                            :href="route('account.index')"
                            class="p-3 rounded-xl transition-all duration-300 hover:scale-110 relative group"
                            :style="iconButtonStyles"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">0</span>
                        </Link>

                        <!-- Sepet -->
                        <Link 
                            :href="route('cart.index')"
                            class="p-3 rounded-xl transition-all duration-300 hover:scale-110 relative group"
                            :style="iconButtonStyles"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span 
                                v-if="cartCount > 0"
                                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center font-bold animate-bounce"
                            >
                                {{ cartCount }}
                            </span>
                        </Link>

                        <!-- Kullanıcı -->
                        <div class="relative" v-if="$page.props.auth?.user">
                            <button
                                @click="userMenuOpen = !userMenuOpen"
                                class="p-3 rounded-xl transition-all duration-300 hover:scale-110 flex items-center space-x-2"
                                :style="iconButtonStyles"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </button>
                            <div 
                                v-if="userMenuOpen"
                                @click.away="userMenuOpen = false"
                                class="absolute right-0 mt-2 w-48 rounded-xl shadow-2xl overflow-hidden animate-fadeIn"
                                :style="dropdownStyles"
                            >
                                <Link :href="route('account.index')" class="block px-4 py-3 hover:opacity-80 transition-all">Hesabım</Link>
                                <Link :href="route('account.orders.index')" class="block px-4 py-3 hover:opacity-80 transition-all">Siparişlerim</Link>
                                <form @submit.prevent="handleLogout" class="block">
                                    <button type="submit" class="w-full text-left px-4 py-3 hover:opacity-80 transition-all">Çıkış Yap</button>
                                </form>
                            </div>
                        </div>
                        <Link 
                            v-else
                            :href="route('login')"
                            class="px-6 py-3 rounded-xl font-semibold transition-all duration-300 hover:scale-105"
                            :style="loginButtonStyles"
                        >
                            Giriş Yap
                        </Link>

                        <!-- Mobil Menü Toggle -->
                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="lg:hidden p-3 rounded-xl transition-all duration-300 hover:scale-110"
                            :style="iconButtonStyles"
                        >
                            <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mega Menü -->
        <nav 
            v-if="themeSettings?.mega_menu_enabled"
            class="mega-menu border-t transition-all duration-500"
            :class="{ 'hidden': mobileMenuOpen }"
            :style="megaMenuStyles"
        >
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between">
                    <!-- Kategori Menüsü -->
                    <div class="flex items-center space-x-1 py-3">
                        <button
                            @click="categoryMenuOpen = !categoryMenuOpen"
                            class="flex items-center space-x-2 px-6 py-3 rounded-xl font-semibold transition-all duration-300 hover:scale-105"
                            :style="categoryButtonStyles"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <span>Kategoriler</span>
                        </button>
                    </div>

                    <!-- Ana Menü -->
                    <div class="hidden lg:flex items-center space-x-1">
                        <template v-for="(menu, index) in menuItems" :key="index">
                            <div 
                                class="relative group"
                                @mouseenter="activeMegaMenu = index"
                                @mouseleave="activeMegaMenu = null"
                            >
                                <Link
                                    :href="menu.href || menu.url || '#'"
                                    class="px-4 py-3 rounded-xl font-semibold transition-all duration-300 hover:scale-105 relative"
                                    :style="menuItemStyles"
                                >
                                    {{ menu.label }}
                                    <span 
                                        v-if="menu.children && menu.children.length > 0"
                                        class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-0 h-1 rounded-full transition-all duration-300 group-hover:w-full"
                                        :style="{ backgroundColor: themeSettings?.accent_color || '#FFE66D' }"
                                    ></span>
                                </Link>

                                <!-- Mega Menü Dropdown -->
                                <div
                                    v-if="menu.children && menu.children.length > 0 && activeMegaMenu === index"
                                    class="absolute left-0 top-full mt-2 w-screen max-w-6xl rounded-2xl shadow-2xl overflow-hidden animate-fadeIn z-50"
                                    :style="megaMenuDropdownStyles"
                                >
                                    <div class="grid grid-cols-4 gap-6 p-6">
                                        <div v-for="(child, childIndex) in menu.children" :key="childIndex" class="space-y-2">
                                            <Link
                                                :href="child.href || child.url || '#'"
                                                class="block font-bold text-lg mb-3 transition-all duration-300 hover:scale-105"
                                                :style="{ color: themeSettings?.primary_color || '#FF6B6B' }"
                                            >
                                                {{ child.label }}
                                            </Link>
                                            <template v-if="child.children">
                                                <Link
                                                    v-for="(subChild, subIndex) in child.children"
                                                    :key="subIndex"
                                                    :href="subChild.href || subChild.url || '#'"
                                                    class="block py-2 text-sm opacity-80 hover:opacity-100 transition-all duration-300 hover:translate-x-2"
                                                >
                                                    {{ subChild.label }}
                                                </Link>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Mobil Menü -->
        <div 
            v-if="mobileMenuOpen"
            class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-50"
            @click="mobileMenuOpen = false"
        >
            <div 
                class="absolute right-0 top-0 h-full w-80 overflow-y-auto animate-slideInRight"
                :style="mobileMenuStyles"
                @click.stop
            >
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold">Menü</h3>
                        <button @click="mobileMenuOpen = false" class="p-2 rounded-lg hover:opacity-80">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <nav class="space-y-2">
                        <Link
                            v-for="(menu, index) in menuItems"
                            :key="index"
                            :href="menu.href || menu.url || '#'"
                            @click="mobileMenuOpen = false"
                            class="block px-4 py-3 rounded-xl font-semibold transition-all duration-300 hover:scale-105"
                            :style="mobileMenuItemStyles"
                        >
                            {{ menu.label }}
                        </Link>
                    </nav>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
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

const page = usePage();
const searchQuery = ref('');
const isScrolled = ref(false);
const mobileMenuOpen = ref(false);
const userMenuOpen = ref(false);
const categoryMenuOpen = ref(false);
const activeMegaMenu = ref(null);

// Tema ayarları
const themeSettings = computed(() => page.props.theme?.settings || {});

// Flash banner (tema ayarlarından)
const flashBanner = computed(() => themeSettings.value.flash_text || null);

// Menü öğeleri
const menuItems = computed(() => {
    const headerMenu = props.menus?.header_main || {};
    return headerMenu.items || [];
});

// Header stilleri
const headerClasses = computed(() => {
    const classes = ['bg-gradient-to-r'];
    if (isScrolled.value) {
        classes.push('shadow-2xl');
    }
    return classes.join(' ');
});

const headerStyles = computed(() => {
    const gradientStart = themeSettings.value.gradient_start || themeSettings.value.primary_color || '#FF6B6B';
    const gradientEnd = themeSettings.value.gradient_end || themeSettings.value.secondary_color || '#4ECDC4';
    
    return {
        background: `linear-gradient(135deg, ${gradientStart} 0%, ${gradientEnd} 100%)`,
        color: '#ffffff',
    };
});

const topBarStyles = computed(() => {
    return {
        background: `linear-gradient(90deg, ${themeSettings.value.primary_color || '#FF6B6B'} 0%, ${themeSettings.value.secondary_color || '#4ECDC4'} 100%)`,
        color: '#ffffff',
    };
});

const mainHeaderStyles = computed(() => {
    return {
        background: 'rgba(255, 255, 255, 0.95)',
        backdropFilter: 'blur(10px)',
    };
});

const flashStyles = computed(() => {
    return {
        background: `linear-gradient(90deg, ${themeSettings.value.accent_color || '#FFE66D'} 0%, ${themeSettings.value.primary_color || '#FF6B6B'} 100%)`,
    };
});

const logoStyles = computed(() => {
    return {
        background: `linear-gradient(135deg, ${themeSettings.value.primary_color || '#FF6B6B'} 0%, ${themeSettings.value.secondary_color || '#4ECDC4'} 100%)`,
    };
});

const logoTextStyles = computed(() => {
    return {
        background: `linear-gradient(135deg, ${themeSettings.value.primary_color || '#FF6B6B'} 0%, ${themeSettings.value.secondary_color || '#4ECDC4'} 100%)`,
        WebkitBackgroundClip: 'text',
        WebkitTextFillColor: 'transparent',
    };
});

const searchBoxStyles = computed(() => {
    return {
        borderColor: themeSettings.value.primary_color || '#FF6B6B',
    };
});

const searchButtonStyles = computed(() => {
    return {
        background: `linear-gradient(135deg, ${themeSettings.value.primary_color || '#FF6B6B'} 0%, ${themeSettings.value.secondary_color || '#4ECDC4'} 100%)`,
        color: '#ffffff',
    };
});

const iconButtonStyles = computed(() => {
    return {
        background: `linear-gradient(135deg, ${themeSettings.value.primary_color || '#FF6B6B'} 0%, ${themeSettings.value.secondary_color || '#4ECDC4'} 100%)`,
        color: '#ffffff',
    };
});

const loginButtonStyles = computed(() => {
    return {
        background: `linear-gradient(135deg, ${themeSettings.value.primary_color || '#FF6B6B'} 0%, ${themeSettings.value.secondary_color || '#4ECDC4'} 100%)`,
        color: '#ffffff',
    };
});

const dropdownStyles = computed(() => {
    return {
        background: 'rgba(255, 255, 255, 0.98)',
        backdropFilter: 'blur(10px)',
        color: '#1f2937',
    };
});

const megaMenuStyles = computed(() => {
    return {
        background: 'rgba(255, 255, 255, 0.98)',
        backdropFilter: 'blur(10px)',
        borderColor: themeSettings.value.accent_color || '#FFE66D',
    };
});

const categoryButtonStyles = computed(() => {
    return {
        background: `linear-gradient(135deg, ${themeSettings.value.primary_color || '#FF6B6B'} 0%, ${themeSettings.value.secondary_color || '#4ECDC4'} 100%)`,
        color: '#ffffff',
    };
});

const menuItemStyles = computed(() => {
    return {
        color: '#1f2937',
    };
});

const megaMenuDropdownStyles = computed(() => {
    return {
        background: 'rgba(255, 255, 255, 0.98)',
        backdropFilter: 'blur(10px)',
    };
});

const mobileMenuStyles = computed(() => {
    return {
        background: `linear-gradient(135deg, ${themeSettings.value.primary_color || '#FF6B6B'} 0%, ${themeSettings.value.secondary_color || '#4ECDC4'} 100%)`,
        color: '#ffffff',
    };
});

const mobileMenuItemStyles = computed(() => {
    return {
        background: 'rgba(255, 255, 255, 0.1)',
        color: '#ffffff',
    };
});

const handleSearch = () => {
    if (searchQuery.value.trim()) {
        router.get(route('home'), { search: searchQuery.value });
    }
};

const handleLogout = () => {
    router.post(route('logout'));
};

const handleScroll = () => {
    isScrolled.value = window.scrollY > 50;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    // Font yükleme
    if (themeSettings.value.font_family) {
        const fontLink = document.createElement('link');
        fontLink.href = `https://fonts.googleapis.com/css2?family=${themeSettings.value.font_family.replace(' ', '+')}:wght@300;400;500;600;700;800&display=swap`;
        fontLink.rel = 'stylesheet';
        document.head.appendChild(fontLink);
    }
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
    }
    to {
        transform: translateX(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}

.animate-slideInRight {
    animation: slideInRight 0.3s ease-out;
}

.flash-banner {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}
</style>



