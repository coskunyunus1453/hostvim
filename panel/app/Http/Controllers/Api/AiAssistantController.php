<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiChatSession;
use App\Services\Ai\AiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    public function __construct(
        private AiAssistantService $assistant,
    ) {}

    public function settings(Request $request): JsonResponse
    {
        return response()->json($this->assistant->settingsForUser($request->user()));
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'providers' => 'required|array|min:1|max:3',
            'providers.*.provider' => 'required|string|in:openai,gemini,anthropic',
            'providers.*.api_key' => 'nullable|string|max:500',
            'providers.*.model' => 'nullable|string|max:120',
            'providers.*.enabled' => 'sometimes|boolean',
            'providers.*.is_default' => 'sometimes|boolean',
        ]);

        $this->assistant->saveSettings($request->user(), $validated['providers']);

        return response()->json([
            'message' => __('ai.settings_saved'),
            'settings' => $this->assistant->settingsForUser($request->user()),
        ]);
    }

    public function testProvider(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:openai,gemini,anthropic',
        ]);

        try {
            $result = $this->assistant->testProvider($request->user(), $validated['provider']);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json(array_merge($result, [
            'settings' => $this->assistant->settingsForUser($request->user()),
        ]), $result['ok'] ? 200 : 422);
    }

    public function usage(Request $request): JsonResponse
    {
        $days = (int) $request->integer('days', 30);

        return response()->json($this->assistant->usageStats($request->user(), $days));
    }

    public function sessions(Request $request): JsonResponse
    {
        $rows = AiChatSession::query()
            ->where('user_id', $request->user()->id)
            ->with(['domain:id,name'])
            ->withCount('messages')
            ->latest('updated_at')
            ->limit(50)
            ->get();

        return response()->json(['sessions' => $rows]);
    }

    public function createSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain_id' => 'nullable|integer|exists:domains,id',
            'context_mode' => 'nullable|string|in:server,site,file',
            'title' => 'nullable|string|max:255',
        ]);

        if (! empty($validated['domain_id'])) {
            $owns = $request->user()->domains()->where('id', $validated['domain_id'])->exists();
            if (! $owns) {
                abort(403);
            }
        }

        $session = AiChatSession::create([
            'user_id' => $request->user()->id,
            'domain_id' => $validated['domain_id'] ?? null,
            'title' => $validated['title'] ?? __('ai.new_chat'),
            'context_mode' => $validated['context_mode'] ?? 'server',
        ]);

        return response()->json(['session' => $session->load('domain:id,name')], 201);
    }

    public function destroySession(Request $request, AiChatSession $aiChatSession): JsonResponse
    {
        if ($aiChatSession->user_id !== $request->user()->id) {
            abort(403);
        }
        $aiChatSession->delete();

        return response()->json(['message' => __('ai.session_deleted')]);
    }

    public function messages(Request $request, AiChatSession $aiChatSession): JsonResponse
    {
        if ($aiChatSession->user_id !== $request->user()->id) {
            abort(403);
        }

        $rows = $aiChatSession->messages()->orderBy('id')->limit(150)->get();

        return response()->json(['messages' => $rows, 'session' => $aiChatSession->load('domain:id,name')]);
    }

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:12000',
            'session_id' => 'nullable|integer|exists:ai_chat_sessions,id',
            'domain_id' => 'nullable|integer|exists:domains,id',
            'context_mode' => 'nullable|string|in:server,site,file',
            'file_path' => 'nullable|string|max:2048',
            'provider' => 'nullable|string|in:openai,gemini,anthropic',
        ]);

        if (! empty($validated['domain_id'])) {
            $owns = $request->user()->domains()->where('id', $validated['domain_id'])->exists();
            if (! $owns) {
                abort(403);
            }
        }

        if (! empty($validated['session_id'])) {
            $session = AiChatSession::query()->find($validated['session_id']);
            if ($session && $session->user_id !== $request->user()->id) {
                abort(403);
            }
        }

        try {
            $result = $this->assistant->chat(
                $request->user(),
                $validated['message'],
                $validated['session_id'] ?? null,
                $validated['domain_id'] ?? null,
                $validated['context_mode'] ?? 'server',
                $validated['file_path'] ?? null,
                $validated['provider'] ?? null,
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'session' => $result['session'],
            'user_message' => $result['user_message'],
            'assistant_message' => $result['assistant_message'],
            'actions' => $result['actions'],
        ]);
    }

    public function applyFix(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain_id' => 'required|integer|exists:domains,id',
            'path' => 'required|string|max:2048',
            'content' => 'required|string|max:500000',
        ]);

        $owns = $request->user()->domains()->where('id', $validated['domain_id'])->exists();
        if (! $owns) {
            abort(403);
        }

        try {
            $result = $this->assistant->applyFix(
                $request->user(),
                (int) $validated['domain_id'],
                $validated['path'],
                $validated['content'],
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['result' => $result]);
    }
}
