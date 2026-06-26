@php($embedded = $embedded ?? false)

@if ($embedded)
    @include('admin.theme-settings._form')
@else
    <x-admin.layout title="Tema & görünüm">
        @include('admin.theme-settings._form')
    </x-admin.layout>
@endif
