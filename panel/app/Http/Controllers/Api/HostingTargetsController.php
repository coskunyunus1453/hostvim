<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HostingSiteTargetResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HostingTargetsController extends Controller
{
    public function __construct(
        private HostingSiteTargetResolver $resolver,
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'targets' => $this->resolver->listTargetsForUser($request->user()),
        ]);
    }
}
