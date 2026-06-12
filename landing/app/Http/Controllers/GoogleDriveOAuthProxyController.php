<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Panelze hub OAuth geri dönüşü — Google yalnızca panelze.com URI'sini görür;
 * kod + state ilgili panel kurulumuna iletilir.
 */
class GoogleDriveOAuthProxyController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $code = trim((string) $request->query('code', ''));
        $state = trim((string) $request->query('state', ''));
        $error = trim((string) $request->query('error', ''));

        if ($error !== '') {
            Log::warning('Google Drive OAuth proxy: Google error', ['error' => $error]);

            return $this->redirectToPanel($state, [
                'gdrive_error' => $error,
            ]);
        }

        if ($code === '' || $state === '') {
            abort(400, 'Missing OAuth code or state');
        }

        return $this->redirectToPanel($state, [
            'code' => $code,
            'state' => $this->panelStateFromComposite($state),
        ]);
    }

    /**
     * @param  array<string, string>  $query
     */
    private function redirectToPanel(string $compositeState, array $query): RedirectResponse
    {
        $panelBase = $this->panelBaseUrlFromComposite($compositeState);
        if ($panelBase === '') {
            abort(400, 'Invalid OAuth state');
        }

        $target = rtrim($panelBase, '/').'/backups/google-callback';
        $qs = http_build_query($query);

        return redirect()->away($target.'?'.$qs);
    }

    private function panelStateFromComposite(string $compositeState): string
    {
        $parts = explode('.', $compositeState, 2);

        return count($parts) === 2 ? $parts[0] : $compositeState;
    }

    private function panelBaseUrlFromComposite(string $compositeState): string
    {
        $parts = explode('.', $compositeState, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return '';
        }

        $decoded = $this->base64UrlDecode($parts[1]);
        if ($decoded === '' || ! filter_var($decoded, FILTER_VALIDATE_URL)) {
            return '';
        }

        $scheme = parse_url($decoded, PHP_URL_SCHEME);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        return rtrim($decoded, '/');
    }

    private function base64UrlDecode(string $value): string
    {
        $value = strtr($value, '-_', '+/');
        $pad = strlen($value) % 4;
        if ($pad > 0) {
            $value .= str_repeat('=', 4 - $pad);
        }
        $raw = base64_decode($value, true);

        return is_string($raw) ? $raw : '';
    }
}
