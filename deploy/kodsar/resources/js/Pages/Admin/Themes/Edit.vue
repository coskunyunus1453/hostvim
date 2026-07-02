<template>
    <AdminLayout>
        <Head :title="`Tema Düzenle: ${theme.name}`" />

        <template #header>
            Tema Düzenle: {{ theme.name }}
        </template>

        <div class="bg-white shadow-sm rounded-lg">
            <form @submit.prevent="submit" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tema Adı *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                        <input
                            v-model="form.slug"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="ornek-tema"
                        />
                        <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Versiyon</label>
                        <input
                            v-model="form.version"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="1.0.0"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Yazar</label>
                        <input
                            v-model="form.author"
                            type="text"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Açıklama</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        ></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ayarlar (JSON)</label>
                        <textarea
                            v-model="settingsJson"
                            rows="5"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg font-mono text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder='{"primary_color":"#2563eb"}'
                        ></textarea>
                        <p v-if="settingsError" class="mt-1 text-sm text-red-600">{{ settingsError }}</p>
                    </div>

                    <div class="md:col-span-2 flex flex-wrap gap-6">
                        <label class="flex items-center">
                            <input v-model="form.is_active" type="checkbox" class="mr-2" />
                            <span>Aktif</span>
                        </label>
                        <label class="flex items-center">
                            <input
                                v-model="form.is_default"
                                type="checkbox"
                                class="mr-2"
                                :disabled="theme.is_default"
                            />
                            <span>Varsayılan tema</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <Link
                        :href="route('admin.themes.index')"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
                    >
                        İptal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Güncelleniyor...' : 'Güncelle' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    theme: Object,
});

const settingsJson = ref(
    props.theme.settings ? JSON.stringify(props.theme.settings, null, 2) : ''
);
const settingsError = ref('');

const form = useForm({
    name: props.theme.name,
    slug: props.theme.slug,
    version: props.theme.version || '1.0.0',
    description: props.theme.description || '',
    author: props.theme.author || '',
    is_active: props.theme.is_active,
    is_default: props.theme.is_default,
    settings: props.theme.settings || null,
});

const submit = () => {
    settingsError.value = '';

    if (settingsJson.value.trim() === '') {
        form.settings = null;
    } else {
        try {
            form.settings = JSON.parse(settingsJson.value);
        } catch {
            settingsError.value = 'Geçerli bir JSON girin.';
            return;
        }
    }

    form.put(route('admin.themes.update', props.theme.id));
};
</script>
