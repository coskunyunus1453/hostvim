const ALLOWED_TAGS = new Set(['P', 'BR', 'STRONG', 'EM', 'B', 'I', 'UL', 'OL', 'LI', 'H1', 'H2', 'H3', 'H4', 'A', 'SPAN', 'DIV'])

function isSafeHref(href: string): boolean {
  const v = href.replace(/[\u0000-\u0020\u007f]+/g, '').toLowerCase()
  if (v === '' || v.startsWith('#') || v.startsWith('/')) return true
  if (/^(https?:|mailto:)/.test(v)) return true
  return !/^[a-z0-9+.-]*:/.test(v)
}

function walk(el: Element): void {
  for (const child of Array.from(el.children)) {
    if (!ALLOWED_TAGS.has(child.tagName)) {
      child.remove()
      continue
    }
    for (const attr of Array.from(child.attributes)) {
      const keep = child.tagName === 'A' && attr.name.toLowerCase() === 'href' && isSafeHref(attr.value)
      if (!keep) child.removeAttribute(attr.name)
    }
    walk(child)
  }
}

/** Bayi onboarding HTML — etiket/öznitelik beyaz listesi (sunucu tarafı sanitize ile birlikte). */
export function sanitizeOnboardingHtml(html: string): string {
  if (typeof DOMParser === 'undefined') return ''
  const doc = new DOMParser().parseFromString(`<div>${html}</div>`, 'text/html')
  const root = doc.body.firstElementChild
  if (!root) return ''
  walk(root)
  return root.innerHTML
}
