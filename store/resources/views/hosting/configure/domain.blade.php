@extends('layouts.app')

@section('content')
<section class="py-10">
    <div class="mx-auto max-w-4xl px-4 lg:px-8">
        <h1 class="text-2xl font-extrabold text-hv-text">{{ $product->name }}</h1>
        <p class="mt-1 text-hv-muted">Hosting hizmetinizde kullanmak istediğiniz alan adını seçin.</p>

        @include('hosting.configure.partials.wizard-nav', ['step' => $step])

        <form method="POST" action="{{ route('hosting.configure.domain.store') }}" class="space-y-6">
            @csrf

            <div class="grid gap-4 sm:grid-cols-3">
                @foreach([
                    'register' => ['Yeni Alan Adı Tescil Et', 'Müsait bir alan adı kaydedelim.'],
                    'transfer' => ['Alan Adını Transfer Et', 'Mevcut alan adınızı bize taşıyın.'],
                    'own' => ['Kendime Ait Alan Adı', 'Alan adınız başka yerde kalsın, DNS yönlendirmesi yapın.'],
                ] as $mode => $meta)
                    <label class="cursor-pointer rounded-2xl border-2 p-5 transition {{ old('domain_mode', $config['domain_mode'] ?? '') === $mode ? 'border-hv-primary bg-hv-primary/5' : 'border-hv-border hover:border-stone-300' }}">
                        <input type="radio" name="domain_mode" value="{{ $mode }}" class="sr-only" @checked(old('domain_mode', $config['domain_mode'] ?? 'own') === $mode) required>
                        <span class="block font-bold text-hv-text">{{ $meta[0] }}</span>
                        <span class="mt-1 block text-sm text-hv-muted">{{ $meta[1] }}</span>
                    </label>
                @endforeach
            </div>

            <div class="rounded-2xl border border-hv-border bg-hv-elevated p-6 shadow-sm">
                <label class="block text-sm font-semibold text-hv-text">Alan adı</label>
                <input type="text" name="domain_name" value="{{ old('domain_name', $config['domain_name'] ?? '') }}" placeholder="ornek.com" required
                    class="mt-2 w-full rounded-xl border border-hv-border px-4 py-3 focus:border-hv-primary focus:ring-[#C2410C]">
                @error('domain_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror

                <div id="domain-years-wrap" class="mt-4 {{ old('domain_mode', $config['domain_mode'] ?? '') === 'register' ? '' : 'hidden' }}">
                    <label class="block text-sm font-semibold text-hv-text">Kayıt süresi (yıl)</label>
                    <select name="domain_years" class="mt-2 rounded-xl border border-hv-border px-4 py-2">
                        @for($y = 1; $y <= 5; $y++)
                            <option value="{{ $y }}" @selected((int) old('domain_years', $config['domain_years'] ?? 1) === $y)>{{ $y }} yıl</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('products.show', [$product->category->slug, $product->slug]) }}" class="rounded-xl border border-hv-border px-6 py-3 font-semibold text-hv-text">Geri</a>
                <button type="submit" class="btn-primary px-8 py-3">Devam Et</button>
            </div>
        </form>
    </div>
</section>
@push('scripts')
<script>
document.querySelectorAll('input[name=domain_mode]').forEach(r => {
    r.addEventListener('change', () => {
        const wrap = document.getElementById('domain-years-wrap');
        wrap.classList.toggle('hidden', r.value !== 'register' || !r.checked);
    });
});
</script>
@endpush
@endsection
