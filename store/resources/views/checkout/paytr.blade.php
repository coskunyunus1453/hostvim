@extends('layouts.app')

@section('title', 'PayTR Ödeme')

@section('content')
<section class="py-8">
    <div class="mx-auto max-w-4xl px-4">
        <h1 class="mb-6 text-2xl font-bold">Güvenli Ödeme — PayTR</h1>
        <iframe src="{{ $result['iframe_url'] }}" id="paytriframe" frameborder="0" scrolling="no" style="width:100%;min-height:600px;"></iframe>
    </div>
</section>
@endsection
