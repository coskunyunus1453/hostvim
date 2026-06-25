@php($embedded = $embedded ?? false)

@if ($embedded)
    @include('admin.install-settings._form')
@else
    <x-admin.layout title="Kurulum komutları">
        @include('admin.install-settings._form')
    </x-admin.layout>
@endif
