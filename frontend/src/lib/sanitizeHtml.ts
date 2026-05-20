const BLOCKED = /<(script|iframe|object|embed|form|input|button|meta|link|style|svg|math)\b[^>]*>[\s\S]*?<\/\1>/gi
const SELF_CLOSING = /<(script|iframe|object|embed|form|input|button|meta|link|style|svg|math)\b[^>]*\/?>/gi
const EVENT_ATTRS = /\s(on\w+|style|formaction|xlink:href)\s*=\s*("[^"]*"|'[^']*'|[^\s>]+)/gi
const JS_PROTO = /javascript\s*:/gi

/** Bayi onboarding HTML — XSS riskini azaltır (sunucu tarafı sanitize ile birlikte). */
export function sanitizeOnboardingHtml(html: string): string {
  return html
    .replace(BLOCKED, '')
    .replace(SELF_CLOSING, '')
    .replace(EVENT_ATTRS, '')
    .replace(JS_PROTO, '')
}
