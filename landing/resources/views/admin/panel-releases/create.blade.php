<x-admin.layout title="Yeni panel sürümü">
    <form method="POST" action="{{ route('admin.panel-releases.store') }}" class="max-w-3xl space-y-4">
        @csrf
        @include('admin.panel-releases._form')
        <button type="submit" class="admin-btn-emerald">Kaydet (taslak)</button>
    </form>
</x-admin.layout>
