#!/bin/bash
set -euo pipefail

# Logo / storage sorunlarını canlıda düzeltir.
# Çalıştır: bash deploy/fix-storage.sh

APP_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$APP_ROOT"

echo "==> Laravel kökü: $APP_ROOT"
echo "==> Aktif disk: $(php artisan tinker --execute="echo config('filesystems.default');" 2>/dev/null | tail -1)"

echo ""
echo "==> storage/app içeriği:"
ls -la storage/app/ 2>/dev/null || true

echo ""
echo "==> Yüklenen görseller (png/jpg/svg/webp/ico):"
find storage -type f \( -iname "*.png" -o -iname "*.jpg" -o -iname "*.jpeg" -o -iname "*.svg" -o -iname "*.webp" -o -iname "*.ico" \) 2>/dev/null | head -40

echo ""
echo "==> Veritabanındaki logo yolları:"
php artisan tinker --execute="
\$rows = \App\Models\SiteSetting::whereIn('key', ['site_logo','site_logo_dark','site_favicon'])->pluck('value','key');
foreach (\$rows as \$k => \$v) { echo \$k . ': ' . \$v . PHP_EOL; }
" 2>/dev/null | tail -10

echo ""
echo "==> public/storage symlink..."
PUBLIC_DIR="${APP_ROOT}/public"
if [[ ! -d "$PUBLIC_DIR" ]]; then
  PUBLIC_DIR="$APP_ROOT"
fi
mkdir -p storage/app/public/branding
chmod -R 775 storage/app/public 2>/dev/null || true

ln -sfn "$(cd storage/app/public && pwd)" "${PUBLIC_DIR}/storage"
echo "    $(ls -la "${PUBLIC_DIR}/storage")"

echo ""
echo "==> private -> public taşıma (varsa)..."
if [ -d "storage/app/private/branding" ]; then
  mkdir -p storage/app/public/branding
  shopt -s nullglob
  for f in storage/app/private/branding/*; do
    base="$(basename "$f")"
    if [ ! -e "storage/app/public/branding/$base" ]; then
      mv "$f" "storage/app/public/branding/"
      echo "    Taşındı: $base"
    fi
  done
fi

# Filament geçici yükleme klasörleri
for dir in storage/app/private/livewire-tmp storage/app/public/livewire-tmp; do
  if [ -d "$dir" ]; then
    echo "    Geçici klasör: $dir"
    ls -la "$dir" 2>/dev/null | head -10
  fi
done

echo ""
echo "==> Config cache temizle..."
php artisan config:clear

echo ""
echo "==> Eksik logo kayıtlarını temizle..."
php artisan tinker --execute="
use App\Models\SiteSetting;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Storage;
foreach (['site_logo','site_logo_dark','site_favicon'] as \$key) {
    \$path = SiteSetting::where('key', \$key)->value('value');
    if (\$path && ! Storage::disk('public')->exists(ltrim((string) \$path, '/'))) {
        SiteSetting::where('key', \$key)->update(['value' => '']);
        echo \"    Temizlendi: \$key\\n\";
    }
}
SettingsService::clearCache();
app(\App\Services\CacheService::class)->clearLayoutCache();
" 2>/dev/null | tail -10

echo ""
echo "==> Kontrol: branding klasörü"
ls -la storage/app/public/branding/ 2>/dev/null || echo "    (hâlâ boş — admin panelden logoyu yeniden yükleyin)"

echo ""
echo "Bitti. Tarayıcıda Ctrl+Shift+R ile yenileyin."
