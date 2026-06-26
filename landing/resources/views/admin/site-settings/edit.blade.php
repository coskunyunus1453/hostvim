@php($embedded = $embedded ?? false)

@if ($embedded)
    @include('admin.site-settings._form')
@else
    <x-admin.layout title="Site ayarları">
        @include('admin.site-settings._form')
    </x-admin.layout>
@endif
