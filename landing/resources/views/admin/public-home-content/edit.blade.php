@php($embedded = $embedded ?? false)

@if ($embedded)
    @include('admin.public-home-content._form')
@else
    <x-admin.layout title="Ön yüz — ana sayfa içeriği">
        @include('admin.public-home-content._form')
    </x-admin.layout>
@endif
