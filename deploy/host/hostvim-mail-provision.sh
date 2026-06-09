#!/usr/bin/env bash
# Engine-state/mail/*.json → Dovecot passwd + Postfix sanal kutular (panel posta hesapları).
set -euo pipefail

STATE_DIR="${1:?engine-state dizini gerekli}"
MAIL_DIR="${STATE_DIR}/mail"
VMAIL_UID=5000
VMAIL_GID=5000
VM_BASE="/var/mail/vmail"

if ! id vmail >/dev/null 2>&1; then
  echo "hostvim-mail-provision: vmail kullanıcısı yok; önce mail-stack-webmail kurun" >&2
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
alias_lines: list[str] = []

if not os.path.isdir(mail_dir):
    mail_dir = mail_dir  # empty

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
        if src and dst and "@" in src:
            alias_lines.append(f"{src}\t{dst}")

def write_lines(path: str, lines: list[str]) -> None:
    tmp = f"{path}.tmp"
    with open(tmp, "w", encoding="utf-8") as fh:
        fh.write("\n".join(lines))
        if lines:
            fh.write("\n")
    os.chmod(tmp, 0o640)
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
echo "OK mail-provision"
