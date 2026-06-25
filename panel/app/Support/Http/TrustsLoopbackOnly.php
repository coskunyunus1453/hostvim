<?php

namespace App\Support\Http;

use Illuminate\Http\Request;

trait TrustsLoopbackOnly
{
  /**
   * Proxy arkasında X-Forwarded-For ile sahtelenebilen Request::ip() yerine
   * doğrudan TCP bağlantı adresini kullanır.
   */
    protected function isLoopbackRequest(Request $request): bool
    {
        $remote = (string) ($request->server('REMOTE_ADDR') ?? '');

        return in_array($remote, ['127.0.0.1', '::1'], true);
    }
}
