<template>
    <div class="min-h-screen flex flex-col">
        <component
            :is="headerComponent"
            :menus="resolvedMenus"
            :cart-count="resolvedCartCount"
        />

        <main class="flex-grow">
            <slot />
        </main>

        <component
            :is="footerComponent"
            :menus="resolvedFooterMenus"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import EcommerceHeader from '@/Components/Frontend/Themes/Ecommerce/Header.vue';
import SoftwareHeader from '@/Components/Frontend/Themes/Software/Header.vue';
import KidsHeader from '@/Components/Frontend/Themes/Kids/Header.vue';
import GamerHeader from '@/Components/Frontend/Themes/Gamer/Header.vue';
import RenkliHeader from '@/Components/Frontend/Themes/Renkli/Header.vue';

import EcommerceFooter from '@/Components/Frontend/Themes/Ecommerce/Footer.vue';
import SoftwareFooter from '@/Components/Frontend/Themes/Software/Footer.vue';
import KidsFooter from '@/Components/Frontend/Themes/Kids/Footer.vue';
import GamerFooter from '@/Components/Frontend/Themes/Gamer/Footer.vue';
import RenkliFooter from '@/Components/Frontend/Themes/Renkli/Footer.vue';

const props = defineProps({
    menus: {
        type: Object,
        default: () => ({}),
    },
    footerMenus: {
        type: Object,
        default: () => ({}),
    },
    cartCount: {
        type: Number,
        default: undefined,
    },
});

const page = usePage();
const themeSlug = computed(() => page.props.theme?.slug || 'ecommerce');

const resolvedMenus = computed(() => {
    if (props.menus?.header_main) {
        return props.menus;
    }
    return page.props.frontend?.menus || props.menus || {};
});

const resolvedFooterMenus = computed(() => {
    const fromPage = props.footerMenus || {};
    const shared = page.props.frontend?.footerMenus || {};

    return {
        footer_links: fromPage.footer_links || shared.footer_links || null,
        footer_legal: fromPage.footer_legal || shared.footer_legal || null,
    };
});

const resolvedCartCount = computed(() => {
    if (props.cartCount !== undefined && props.cartCount !== null) {
        return props.cartCount;
    }
    return page.props.cartCount ?? page.props.frontend?.cartCount ?? 0;
});

const headerComponent = computed(() => {
    const components = {
        ecommerce: EcommerceHeader,
        software: SoftwareHeader,
        kids: KidsHeader,
        gamer: GamerHeader,
        renkli: RenkliHeader,
    };
    return components[themeSlug.value] || EcommerceHeader;
});

const footerComponent = computed(() => {
    const components = {
        ecommerce: EcommerceFooter,
        software: SoftwareFooter,
        kids: KidsFooter,
        gamer: GamerFooter,
        renkli: RenkliFooter,
    };
    return components[themeSlug.value] || EcommerceFooter;
});
</script>
