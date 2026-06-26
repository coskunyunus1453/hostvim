<template>
    <FrontendLayout :menus="menus" :footer-menus="footerMenus" :cart-count="cartCount">
        <Head :title="page.title + ' - Kodsar'" />

        <div class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
            <div class="container mx-auto px-4 py-10 max-w-4xl">
                <nav class="mb-6">
                    <ol class="flex items-center flex-wrap gap-2 text-sm text-gray-500">
                        <li>
                            <Link :href="route('home')" class="text-primary-600 hover:text-primary-700 font-medium">
                                Ana Sayfa
                            </Link>
                        </li>
                        <li>/</li>
                        <li class="text-gray-900 font-semibold">{{ page.title }}</li>
                    </ol>
                </nav>

                <article class="bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden">
                    <header class="px-6 md:px-10 py-8 border-b border-gray-100 bg-gradient-to-r from-primary-600/10 to-secondary-600/10">
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-900">{{ page.title }}</h1>
                        <p v-if="page.updated_at" class="mt-2 text-sm text-gray-500">
                            Son güncelleme: {{ page.updated_at }}
                        </p>
                    </header>
                    <div
                        class="legal-content px-6 md:px-10 py-8 prose prose-lg max-w-none prose-headings:text-gray-900 prose-a:text-primary-600"
                        v-html="page.content"
                    />
                </article>
            </div>
        </div>
    </FrontendLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import FrontendLayout from '@/Layouts/FrontendLayout.vue';
import { route } from 'ziggy-js';

defineProps({
    page: { type: Object, required: true },
    menus: { type: Object, default: () => ({}) },
    footerMenus: { type: Object, default: () => ({}) },
    cartCount: { type: Number, default: 0 },
});
</script>

<style scoped>
:deep(.legal-content h2) {
    margin-top: 2rem;
    margin-bottom: 0.75rem;
    font-size: 1.35rem;
    font-weight: 700;
}
:deep(.legal-content h3) {
    margin-top: 1.25rem;
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
    font-weight: 600;
}
:deep(.legal-content ul),
:deep(.legal-content ol) {
    padding-left: 1.25rem;
    margin: 0.75rem 0;
}
:deep(.legal-content li) {
    margin: 0.35rem 0;
}
:deep(.legal-content p) {
    margin: 0.75rem 0;
    line-height: 1.7;
    color: #374151;
}
</style>
