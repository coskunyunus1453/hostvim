<?php

namespace App\Services;

/**
 * Engine stack-scan çıktısını locale'e göre çevirir (API + bildirimler).
 */
class SiteStackTranslator
{
    /**
     * @param  array<string, mixed>  $scan
     * @return array<string, mixed>
     */
    public function localizeScan(array $scan): array
    {
        $issues = [];
        foreach ((array) ($scan['issues'] ?? []) as $issue) {
            if (! is_array($issue)) {
                continue;
            }
            if ($this->shouldSkipIssue($issue, $scan)) {
                continue;
            }
            $issues[] = $this->localizeIssue($issue);
        }
        $scan['issues'] = $issues;

        $cur = rtrim((string) ($scan['current_doc_root'] ?? ''), '/');
        $rec = rtrim((string) ($scan['recommended_doc_root'] ?? ''), '/');
        $scan['docroot_aligned'] = $cur !== '' && $rec !== '' && $cur === $rec;

        $gk = (string) ($scan['guidance_key'] ?? '');
        if ($gk !== '') {
            $key = 'domains.'.$gk;
            $text = __($key);
            $scan['guidance'] = $text !== $key ? $text : '';
        }

        $profile = (string) ($scan['profile'] ?? 'standard');
        $scan['profile_label'] = $this->profileLabel($profile);

        return $scan;
    }

    /**
     * @param  array<string, mixed>  $issue
     * @return array<string, mixed>
     */
    public function localizeIssue(array $issue): array
    {
        $code = (string) ($issue['code'] ?? '');
        $params = (array) ($issue['params'] ?? []);
        if (isset($params['profile'])) {
            $params['profile'] = $this->profileLabel((string) $params['profile']);
        }
        $key = 'domains.stack_issues.'.$code;
        $message = __($key, $params);
        if ($message === $key) {
            $message = $code;
        }
        $issue['message'] = $message;

        return $issue;
    }

    /**
     * @param  array<string, mixed>  $issue
     * @param  array<string, mixed>  $scan
     */
    private function shouldSkipIssue(array $issue, array $scan): bool
    {
        $code = (string) ($issue['code'] ?? '');
        if ($code === 'docroot_mismatch') {
            $params = (array) ($issue['params'] ?? []);
            $current = rtrim((string) ($params['current'] ?? $scan['current_doc_root'] ?? ''), '/');
            $recommended = rtrim((string) ($params['recommended'] ?? $scan['recommended_doc_root'] ?? ''), '/');

            return $current !== '' && $current === $recommended;
        }

        return false;
    }

    public function profileLabel(string $profile): string
    {
        $key = 'domains.stack_profiles.'.$profile;
        $label = __($key);

        return $label !== $key ? $label : $profile;
    }

    /**
     * @param  list<string>  $codes
     */
    public function alertTitle(string $domainName, string $profile, int $issueCount): string
    {
        return __('domains.stack_alert_title', [
            'domain' => $domainName,
            'profile' => $this->profileLabel($profile),
            'count' => $issueCount,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $issues
     */
    public function alertMessage(array $issues): string
    {
        $lines = [];
        foreach (array_slice($issues, 0, 4) as $issue) {
            if (is_array($issue) && ! empty($issue['message'])) {
                $lines[] = (string) $issue['message'];
            }
        }
        if (count($issues) > 4) {
            $lines[] = __('domains.stack_alert_more', ['count' => count($issues) - 4]);
        }

        return implode("\n", $lines);
    }
}
