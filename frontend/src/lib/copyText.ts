import toast from 'react-hot-toast'

function legacyCopyText(text: string): boolean {
  try {
    const ta = document.createElement('textarea')
    ta.value = text
    ta.style.position = 'fixed'
    ta.style.left = '-9999px'
    document.body.appendChild(ta)
    ta.select()
    const ok = document.execCommand('copy')
    document.body.removeChild(ta)
    return ok
  } catch {
    return false
  }
}

/** Şifre gibi hassas metinleri panoya kopyalar; toast içinde düz metin göstermez. */
export async function copyPlaintextWithToasts(text: string, messages: { ok: string; fail: string }): Promise<void> {
  try {
    const clip = typeof navigator !== 'undefined' ? navigator.clipboard : undefined
    if (clip?.writeText && window.isSecureContext) {
      await clip.writeText(text)
    } else {
      const ok = legacyCopyText(text)
      if (!ok) throw new Error('copy-failed')
    }
    toast.success(messages.ok)
  } catch {
    const ok = legacyCopyText(text)
    if (ok) {
      toast.success(messages.ok)
      return
    }
    toast.error(messages.fail)
  }
}
