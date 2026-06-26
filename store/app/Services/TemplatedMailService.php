<?php

namespace App\Services;

use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TemplatedMailService
{
    public function __construct(
        protected MailBrandingService $branding,
    ) {}

    /**
     * @param  array<string, string>  $replacements
     */
    public function send(string $slug, string $to, array $replacements): bool
    {
        $rendered = $this->render($slug, $replacements);
        if ($rendered === null) {
            return false;
        }

        try {
            Mail::to($to)->queue(new TemplatedMail($rendered['subject'], $rendered['body']));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Store templated mail failed', [
                'slug' => $slug,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param  array<string, string>  $replacements
     * @return array{subject: string, body: string}|null
     */
    public function render(string $slug, array $replacements): ?array
    {
        $template = EmailTemplate::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if ($template === null) {
            return null;
        }

        $merged = array_merge($this->branding->replacements(), $replacements);

        return [
            'subject' => $this->replace($template->subject, $merged),
            'body' => $this->replace($template->body, $merged),
        ];
    }

    /**
     * @param  array<string, string>  $replacements
     */
    public function replace(string $text, array $replacements): string
    {
        foreach ($replacements as $key => $value) {
            $text = str_replace('{'.$key.'}', $value, $text);
        }

        return $text;
    }
}
