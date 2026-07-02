<template>
    <AdminLayout>
        <Head :title="`Tema: ${theme.name}`" />

        <template #header>
            {{ theme.name }}
        </template>

        <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">Slug</dt>
                    <dd class="font-medium text-gray-900">{{ theme.slug }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Versiyon</dt>
                    <dd class="font-medium text-gray-900">{{ theme.version || '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Yazar</dt>
                    <dd class="font-medium text-gray-900">{{ theme.author || '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Durum</dt>
                    <dd class="font-medium text-gray-900">
                        <span v-if="theme.is_default">Varsayılan</span>
                        <span v-else-if="theme.is_active">Aktif</span>
                        <span v-else>Pasif</span>
                    </dd>
                </div>
                <div class="md:col-span-2" v-if="theme.description">
                    <dt class="text-gray-500">Açıklama</dt>
                    <dd class="mt-1 text-gray-900">{{ theme.description }}</dd>
                </div>
            </dl>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <Link :href="route('admin.themes.index')" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Listeye dön
                </Link>
                <Link :href="route('admin.themes.edit', theme.id)" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Düzenle
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    theme: Object,
});
</script>
