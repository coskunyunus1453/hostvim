import { computed } from 'vue';
import { route } from 'ziggy-js';

export function useFooterMenus(menus) {
    const footerMenuItems = computed(() => menus?.footer_links?.items || []);
    const legalMenuItems = computed(() => menus?.footer_legal?.items || []);

    const menuHref = (item) => item?.href || item?.url || '#';
    const legalLink = (slug) => route('page.show', slug);

    return {
        footerMenuItems,
        legalMenuItems,
        menuHref,
        legalLink,
    };
}

export function useHeaderMenus(menus) {
    const menuItems = computed(() => menus?.header_main?.items || []);
    const menuHref = (item) => item?.href || item?.url || '#';

    return { menuItems, menuHref };
}
