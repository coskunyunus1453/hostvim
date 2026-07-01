<?php

namespace App\Http\Controllers;

use App\Services\Domain\DomainValueService;
use App\Services\SeoService;
use App\Support\DomainValueContent;
use App\Support\DomainValueFaq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DomainValueController extends Controller
{
    public function index(SeoService $seo)
    {
        $content = DomainValueContent::all();

        return view('domain.value', [
            'content' => $content,
            'faqs' => DomainValueFaq::all(),
            'seo' => $seo->forDomainValue(),
            'breadcrumbs' => [
                ['label' => 'Ana Sayfa', 'url' => route('home')],
                ['label' => 'Domain Değer Sorgulama', 'url' => null],
            ],
        ]);
    }

    public function estimate(Request $request, DomainValueService $valuation): JsonResponse
    {
        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:253'],
        ]);

        try {
            return response()->json($valuation->estimate($validated['domain']));
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->validator->errors()->first('domain') ?? 'Geçersiz alan adı.',
            ], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Değer hesaplanamadı. Lütfen tekrar deneyin.'], 500);
        }
    }
}
