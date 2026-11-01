#!/usr/bin/env bash
# Engine-state/mail/*.json → Dovecot passwd + Postfix sanal kutular (panel posta hesapları).
set -euo pipefail

STATE_DIR="${1:?engine-state dizini gerekli}"
MAIL_DIR="${STATE_DIR}/mail"
VMAIL_UID=5000
VMAIL_GID=5000
VM_BASE="/var/mail/vmail"

if ! id vmail >/dev/null 2>&1; then
  echo "panelze-mail-provision: vmail kullanıcısı yok; önce mail-stack-webmail kurun" >&2
  exit 1
fi

mkdir -p "$VM_BASE"
chown vmail:vmail "$VM_BASE"

python3 - "$MAIL_DIR" "$VM_BASE" "$VMAIL_UID" "$VMAIL_GID" <<'PY'
import glob
import json
import os
import subprocess
import sys

mail_dir, vm_base, uid_s, gid_s = sys.argv[1:5]
uid = int(uid_s)
gid = int(gid_s)

passwd_lines: list[str] = []
domains: set[str] = set()
vmap_lines: list[str] = []
# source -> {"dests": ordered unique list, "keep_copy": bool}
alias_by_src: dict[str, dict] = {}
mailbox_emails: set[str] = set()

if not os.path.isdir(mail_dir):
    mail_dir = mail_dir  # empty

def truthy(v) -> bool:
    if isinstance(v, bool):
        return v
    if isinstance(v, (int, float)):
        return v != 0
    s = str(v).strip().lower()
    return s in ("1", "true", "yes", "on")

for path in sorted(glob.glob(os.path.join(mail_dir, "*.json"))):
    domain_key = os.path.basename(path)[:-5]
    try:
        with open(path, encoding="utf-8") as fh:
            data = json.load(fh)
    except (OSError, json.JSONDecodeError):
        continue
    for mb in data.get("mailboxes") or []:
        email = str(mb.get("email", "")).strip().lower()
        password = str(mb.get("password", ""))
        if not email or "@" not in email or not password:
            continue
        local, dom = email.split("@", 1)
        domains.add(dom)
        mailbox_emails.add(email)
        maildir = os.path.join(vm_base, dom, local)
        os.makedirs(maildir, exist_ok=True)
        os.chown(maildir, uid, gid)
        proc = subprocess.run(
            ["doveadm", "pw", "-s", "SHA512-CRYPT", "-p", password],
            capture_output=True,
            text=True,
            check=False,
        )
        if proc.returncode != 0:
            raise SystemExit(f"doveadm pw failed for {email}: {proc.stderr.strip()}")
        hashed = proc.stdout.strip()
        passwd_lines.append(f"{email}:{hashed}:{uid}:{gid}::{maildir}")
        vmap_lines.append(f"{email}\t{dom}/{local}/")
    for fw in data.get("forwarders") or []:
        src = str(fw.get("source", "")).strip().lower()
        dst = str(fw.get("destination", "")).strip().lower()
        if not src or not dst or "@" not in src or "@" not in dst:
            continue
        if src == dst:
            continue
        _, src_dom = src.split("@", 1)
        if src_dom:
            domains.add(src_dom)
        entry = alias_by_src.setdefault(src, {"dests": [], "keep_copy": False})
        if truthy(fw.get("keep_copy")):
            entry["keep_copy"] = True
        if dst not in entry["dests"]:
            entry["dests"].append(dst)

# Postfix hash:/ maps: aynı key bir kez olmalı; çoklu hedef virgülle birleştirilir.
# Yerel kutuya kopya için keep_copy=true iken source hedef listesine eklenir
# (aksi halde alias kutuyu tamamen baypas eder → webmail boş kalır).
alias_lines: list[str] = []
for src in sorted(alias_by_src.keys()):
    entry = alias_by_src[src]
    dests = list(entry["dests"])
    keep = bool(entry["keep_copy"])
    # Yerel mailbox kaynağında keep_copy işaretliyse (veya hiç hedef yoksa) kopya bırak.
    if keep and src in mailbox_emails and src not in dests:
        dests.append(src)
    if not dests:
        continue
    alias_lines.append(f"{src}\t{','.join(dests)}")

def write_lines(path: str, lines: list[str]) -> None:
    tmp = f"{path}.tmp"
    with open(tmp, "w", encoding="utf-8") as fh:
        fh.write("\n".join(lines))
        if lines:
            fh.write("\n")
    os.chmod(tmp, 0o640)
    try:
        import grp

        gid = grp.getgrnam("dovecot").gr_gid
        os.chown(tmp, 0, gid)
    except (KeyError, PermissionError, OSError):
        pass
    os.replace(tmp, path)

write_lines("/etc/dovecot/passwd", passwd_lines)
write_lines("/etc/postfix/virtual_mailbox_domains", sorted(domains))
write_lines("/etc/postfix/virtual_mailbox_maps", vmap_lines)
write_lines("/etc/postfix/virtual_alias_maps", alias_lines)
PY

postmap /etc/postfix/virtual_mailbox_maps
postmap /etc/postfix/virtual_alias_maps
systemctl reload postfix >/dev/null 2>&1 || true
systemctl reload dovecot >/dev/null 2>&1 || true

DKIM_SYNC="/usr/local/sbin/panelze-mail-dkim-sync"
[[ -x "$DKIM_SYNC" ]] && "$DKIM_SYNC" "$STATE_DIR" || true

postmap /etc/postfix/virtual_mailbox_domains 2>/dev/null || true

echo "OK mail-provision"
