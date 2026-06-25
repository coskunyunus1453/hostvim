@extends('layouts.app')

@section('title', 'Alan Adı Sorgula')

@section('content')
<section class="py-16">
    <div class="mx-auto max-w-3xl px-4 lg:px-8">
        <h1 class="text-3xl font-bold text-stone-900">Alan adı sorgula</h1>
        <p class="mt-2 text-stone-600">Müsait domainleri sepete ekleyin; ödeme sonrası otomatik kayıt edilir.</p>

        <div class="card mt-8">
            <form id="domain-search-form" class="flex flex-col gap-3 sm:flex-row">
                @csrf
                <input type="text" id="domain-input" name="domain" placeholder="ornek.com" required
                    class="flex-1 rounded-xl border border-stone-300 px-4 py-3" autocomplete="off">
                <button type="submit" class="btn-primary px-6 py-3">Sorgula</button>
            </form>
            <div id="domain-result" class="mt-4 hidden rounded-xl border px-4 py-3 text-sm"></div>
        </div>

        @if(count($tlds) > 0)
            <div class="mt-8">
                <h2 class="font-semibold text-stone-900">Popüler uzantılar</h2>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach(array_slice($tlds, 0, 12) as $tld)
                        <button type="button" class="tld-pick rounded-full border border-stone-200 px-3 py-1 text-sm hover:border-[#C2410C]"
                            data-tld="{{ $tld['tld'] }}">{{ $tld['tld'] }}</button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>

@push('scripts')
<script>
document.getElementById('domain-search-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const domain = document.getElementById('domain-input').value.trim();
    const box = document.getElementById('domain-result');
    box.classList.remove('hidden');
    box.className = 'mt-4 rounded-xl border px-4 py-3 text-sm border-stone-200';
    box.textContent = 'Kontrol ediliyor...';
    const res = await fetch('{{ route('domain.check') }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
        body: JSON.stringify({domain}),
    });
    const data = await res.json();
    if (!res.ok) {
        box.className += ' border-red-200 bg-red-50 text-red-700';
        box.textContent = data.message || 'Sorgu başarısız.';
        return;
    }
    if (data.available) {
        box.className += ' border-green-200 bg-green-50 text-green-800';
        box.innerHTML = `<strong>${data.domain}</strong> müsait — ₺${Number(data.register_price).toFixed(2)}/yıl
            <button type="button" id="add-domain-btn" class="ml-3 rounded-lg bg-[#C2410C] px-3 py-1 text-white text-xs font-semibold">Sepete Ekle</button>`;
        document.getElementById('add-domain-btn').onclick = async () => {
            const add = await fetch('{{ route('domain.cart.add') }}', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
                body: JSON.stringify({domain: data.domain, years: 1}),
            });
            const addData = await add.json();
            if (add.ok && addData.redirect) window.location = addData.redirect;
            else alert(addData.message || 'Sepete eklenemedi.');
        };
    } else {
        box.className += ' border-amber-200 bg-amber-50 text-amber-900';
        box.textContent = `${data.domain} müsait değil.`;
    }
});
document.querySelectorAll('.tld-pick').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById('domain-input');
        const v = input.value.replace(/\.[a-z.]+$/i, '');
        input.value = (v || 'markam') + btn.dataset.tld;
    });
});
</script>
@endpush
@endsection
