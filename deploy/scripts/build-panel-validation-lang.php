#!/usr/bin/env php
<?php
/**
 * Panel API validation çevirileri — Laravel-Lang + panel alan adları.
 * Çalıştırma: php deploy/scripts/build-panel-validation-lang.php
 */
declare(strict_types=1);

$repoRoot = dirname(__DIR__, 2);
$outDir = $repoRoot.'/panel/resources/lang';

$localeSources = [
    'en' => $repoRoot.'/panel/vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php',
    'tr' => $repoRoot.'/landing/lang/tr/validation.php',
    'de' => 'https://raw.githubusercontent.com/Laravel-Lang/lang/main/locales/de/php.json',
    'fr' => 'https://raw.githubusercontent.com/Laravel-Lang/lang/main/locales/fr/php.json',
    'es' => 'https://raw.githubusercontent.com/Laravel-Lang/lang/main/locales/es/php.json',
    'pt' => 'https://raw.githubusercontent.com/Laravel-Lang/lang/main/locales/pt/php.json',
    'ru' => 'https://raw.githubusercontent.com/Laravel-Lang/lang/main/locales/ru/php.json',
    'ja' => 'https://raw.githubusercontent.com/Laravel-Lang/lang/main/locales/ja/php.json',
    'zh' => 'https://raw.githubusercontent.com/Laravel-Lang/lang/main/locales/zh_CN/php.json',
    'ar' => 'https://raw.githubusercontent.com/Laravel-Lang/lang/main/locales/ar/php.json',
];

$panelAttributes = [
    'en' => [
        'password' => 'password',
        'password_confirmation' => 'password confirmation',
        'current_password' => 'current password',
        'email' => 'email address',
        'name' => 'name',
        'locale' => 'language',
        'phone' => 'phone',
        'domain' => 'domain',
        'hostname' => 'hostname',
        'path_segment' => 'path segment',
        'role' => 'role',
        'package_id' => 'package',
        'quota_mb' => 'quota',
        'command' => 'command',
        'schedule' => 'schedule',
        'description' => 'description',
        'token' => 'token',
        'code' => 'code',
        'two_factor_code' => 'two-factor code',
        'panel_user_id' => 'panel user',
        'host' => 'host',
        'port' => 'port',
        'username' => 'username',
        'database' => 'database',
        'record_type' => 'record type',
        'record_name' => 'record name',
        'record_value' => 'record value',
        'ttl' => 'TTL',
    ],
    'tr' => [
        'password' => 'şifre',
        'password_confirmation' => 'şifre tekrarı',
        'current_password' => 'mevcut şifre',
        'email' => 'e-posta',
        'name' => 'ad soyad',
        'locale' => 'dil',
        'phone' => 'telefon',
        'domain' => 'alan adı',
        'hostname' => 'hostname',
        'path_segment' => 'yol segmenti',
        'role' => 'rol',
        'package_id' => 'paket',
        'quota_mb' => 'kota',
        'command' => 'komut',
        'schedule' => 'zamanlama',
        'description' => 'açıklama',
        'token' => 'anahtar',
        'code' => 'kod',
        'two_factor_code' => 'iki faktörlü kod',
        'panel_user_id' => 'panel kullanıcısı',
        'host' => 'sunucu',
        'port' => 'port',
        'username' => 'kullanıcı adı',
        'database' => 'veritabanı',
        'record_type' => 'kayıt türü',
        'record_name' => 'kayıt adı',
        'record_value' => 'kayıt değeri',
        'ttl' => 'TTL',
    ],
    'de' => [
        'password' => 'Passwort',
        'password_confirmation' => 'Passwort-Bestätigung',
        'current_password' => 'aktuelles Passwort',
        'email' => 'E-Mail-Adresse',
        'name' => 'Name',
        'locale' => 'Sprache',
        'phone' => 'Telefon',
        'domain' => 'Domain',
        'hostname' => 'Hostname',
        'path_segment' => 'Pfadsegment',
        'role' => 'Rolle',
        'package_id' => 'Paket',
        'quota_mb' => 'Kontingent',
        'command' => 'Befehl',
        'schedule' => 'Zeitplan',
        'description' => 'Beschreibung',
        'token' => 'Token',
        'code' => 'Code',
        'two_factor_code' => 'Zwei-Faktor-Code',
        'panel_user_id' => 'Panel-Benutzer',
        'host' => 'Host',
        'port' => 'Port',
        'username' => 'Benutzername',
        'database' => 'Datenbank',
        'record_type' => 'Datensatztyp',
        'record_name' => 'Datensatzname',
        'record_value' => 'Datensatzwert',
        'ttl' => 'TTL',
    ],
    'fr' => [
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'current_password' => 'mot de passe actuel',
        'email' => 'adresse e-mail',
        'name' => 'nom',
        'locale' => 'langue',
        'phone' => 'téléphone',
        'domain' => 'domaine',
        'hostname' => 'nom d\'hôte',
        'path_segment' => 'segment de chemin',
        'role' => 'rôle',
        'package_id' => 'forfait',
        'quota_mb' => 'quota',
        'command' => 'commande',
        'schedule' => 'planification',
        'description' => 'description',
        'token' => 'jeton',
        'code' => 'code',
        'two_factor_code' => 'code à deux facteurs',
        'panel_user_id' => 'utilisateur du panel',
        'host' => 'hôte',
        'port' => 'port',
        'username' => 'nom d\'utilisateur',
        'database' => 'base de données',
        'record_type' => 'type d\'enregistrement',
        'record_name' => 'nom d\'enregistrement',
        'record_value' => 'valeur d\'enregistrement',
        'ttl' => 'TTL',
    ],
    'es' => [
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de contraseña',
        'current_password' => 'contraseña actual',
        'email' => 'correo electrónico',
        'name' => 'nombre',
        'locale' => 'idioma',
        'phone' => 'teléfono',
        'domain' => 'dominio',
        'hostname' => 'nombre de host',
        'path_segment' => 'segmento de ruta',
        'role' => 'rol',
        'package_id' => 'paquete',
        'quota_mb' => 'cuota',
        'command' => 'comando',
        'schedule' => 'programación',
        'description' => 'descripción',
        'token' => 'token',
        'code' => 'código',
        'two_factor_code' => 'código de dos factores',
        'panel_user_id' => 'usuario del panel',
        'host' => 'host',
        'port' => 'puerto',
        'username' => 'nombre de usuario',
        'database' => 'base de datos',
        'record_type' => 'tipo de registro',
        'record_name' => 'nombre de registro',
        'record_value' => 'valor de registro',
        'ttl' => 'TTL',
    ],
    'pt' => [
        'password' => 'senha',
        'password_confirmation' => 'confirmação de senha',
        'current_password' => 'senha atual',
        'email' => 'e-mail',
        'name' => 'nome',
        'locale' => 'idioma',
        'phone' => 'telefone',
        'domain' => 'domínio',
        'hostname' => 'hostname',
        'path_segment' => 'segmento de caminho',
        'role' => 'função',
        'package_id' => 'pacote',
        'quota_mb' => 'cota',
        'command' => 'comando',
        'schedule' => 'agendamento',
        'description' => 'descrição',
        'token' => 'token',
        'code' => 'código',
        'two_factor_code' => 'código de dois fatores',
        'panel_user_id' => 'usuário do painel',
        'host' => 'host',
        'port' => 'porta',
        'username' => 'nome de usuário',
        'database' => 'banco de dados',
        'record_type' => 'tipo de registro',
        'record_name' => 'nome do registro',
        'record_value' => 'valor do registro',
        'ttl' => 'TTL',
    ],
    'ru' => [
        'password' => 'пароль',
        'password_confirmation' => 'подтверждение пароля',
        'current_password' => 'текущий пароль',
        'email' => 'электронная почта',
        'name' => 'имя',
        'locale' => 'язык',
        'phone' => 'телефон',
        'domain' => 'домен',
        'hostname' => 'имя хоста',
        'path_segment' => 'сегмент пути',
        'role' => 'роль',
        'package_id' => 'пакет',
        'quota_mb' => 'квота',
        'command' => 'команда',
        'schedule' => 'расписание',
        'description' => 'описание',
        'token' => 'токен',
        'code' => 'код',
        'two_factor_code' => 'код двухфакторной аутентификации',
        'panel_user_id' => 'пользователь панели',
        'host' => 'хост',
        'port' => 'порт',
        'username' => 'имя пользователя',
        'database' => 'база данных',
        'record_type' => 'тип записи',
        'record_name' => 'имя записи',
        'record_value' => 'значение записи',
        'ttl' => 'TTL',
    ],
    'ja' => [
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード（確認）',
        'current_password' => '現在のパスワード',
        'email' => 'メールアドレス',
        'name' => '名前',
        'locale' => '言語',
        'phone' => '電話番号',
        'domain' => 'ドメイン',
        'hostname' => 'ホスト名',
        'path_segment' => 'パスセグメント',
        'role' => 'ロール',
        'package_id' => 'パッケージ',
        'quota_mb' => 'クォータ',
        'command' => 'コマンド',
        'schedule' => 'スケジュール',
        'description' => '説明',
        'token' => 'トークン',
        'code' => 'コード',
        'two_factor_code' => '二要素認証コード',
        'panel_user_id' => 'パネルユーザー',
        'host' => 'ホスト',
        'port' => 'ポート',
        'username' => 'ユーザー名',
        'database' => 'データベース',
        'record_type' => 'レコードタイプ',
        'record_name' => 'レコード名',
        'record_value' => 'レコード値',
        'ttl' => 'TTL',
    ],
    'zh' => [
        'password' => '密码',
        'password_confirmation' => '确认密码',
        'current_password' => '当前密码',
        'email' => '电子邮箱',
        'name' => '姓名',
        'locale' => '语言',
        'phone' => '电话',
        'domain' => '域名',
        'hostname' => '主机名',
        'path_segment' => '路径段',
        'role' => '角色',
        'package_id' => '套餐',
        'quota_mb' => '配额',
        'command' => '命令',
        'schedule' => '计划',
        'description' => '描述',
        'token' => '令牌',
        'code' => '代码',
        'two_factor_code' => '双因素验证码',
        'panel_user_id' => '面板用户',
        'host' => '主机',
        'port' => '端口',
        'username' => '用户名',
        'database' => '数据库',
        'record_type' => '记录类型',
        'record_name' => '记录名称',
        'record_value' => '记录值',
        'ttl' => 'TTL',
    ],
    'ar' => [
        'password' => 'كلمة المرور',
        'password_confirmation' => 'تأكيد كلمة المرور',
        'current_password' => 'كلمة المرور الحالية',
        'email' => 'البريد الإلكتروني',
        'name' => 'الاسم',
        'locale' => 'اللغة',
        'phone' => 'الهاتف',
        'domain' => 'النطاق',
        'hostname' => 'اسم المضيف',
        'path_segment' => 'جزء المسار',
        'role' => 'الدور',
        'package_id' => 'الباقة',
        'quota_mb' => 'الحصة',
        'command' => 'الأمر',
        'schedule' => 'الجدولة',
        'description' => 'الوصف',
        'token' => 'الرمز',
        'code' => 'الكود',
        'two_factor_code' => 'رمز المصادقة الثنائية',
        'panel_user_id' => 'مستخدم اللوحة',
        'host' => 'المضيف',
        'port' => 'المنفذ',
        'username' => 'اسم المستخدم',
        'database' => 'قاعدة البيانات',
        'record_type' => 'نوع السجل',
        'record_name' => 'اسم السجل',
        'record_value' => 'قيمة السجل',
        'ttl' => 'TTL',
    ],
];

function loadValidationArray(string $source): array
{
    if (str_starts_with($source, 'http://') || str_starts_with($source, 'https://')) {
        $json = file_get_contents($source);
        if ($json === false) {
            throw new RuntimeException("Cannot fetch: {$source}");
        }
        $flat = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $nested = [];
        foreach ($flat as $key => $value) {
            if (! is_string($value)) {
                continue;
            }
            // Laravel-Lang auth.password conflicts with validation password array key
            if ($key === 'password' && ! str_contains($value, ':attribute') && ! str_contains($value, ':Attribute')) {
                continue;
            }
            $parts = explode('.', $key);
            if (count($parts) === 1) {
                $nested[$key] = normalizePlaceholders($value);
            } else {
                $ref = &$nested;
                foreach ($parts as $i => $part) {
                    if ($i === count($parts) - 1) {
                        $ref[$part] = normalizePlaceholders($value);
                    } else {
                        if (! isset($ref[$part]) || ! is_array($ref[$part])) {
                            $ref[$part] = [];
                        }
                        $ref = &$ref[$part];
                    }
                }
            }
        }

        return $nested;
    }

    if (! is_file($source)) {
        throw new RuntimeException("Missing source: {$source}");
    }

    /** @var array $data */
    $data = require $source;

    return $data;
}

function normalizePlaceholders(string $value): string
{
    return str_replace([':Attribute', ':Other', ':Values', ':Value', ':Date', ':Format', ':Min', ':Max', ':Digits', ':Decimal'],
        [':attribute', ':other', ':values', ':value', ':date', ':format', ':min', ':max', ':digits', ':decimal'],
        $value);
}

function exportValidationPhp(array $data): string
{
    return "<?php\n\nreturn ".arrayToPhp($data, 0).";\n";
}

function arrayToPhp(array $array, int $indent): string
{
    $pad = str_repeat('    ', $indent);
    $inner = str_repeat('    ', $indent + 1);
    $lines = ['['];
    foreach ($array as $key => $value) {
        $keyPart = is_int($key) ? (string) $key : var_export((string) $key, true);
        if (is_array($value)) {
            $lines[] = "{$inner}{$keyPart} => ".arrayToPhp($value, $indent + 1).',';
        } else {
            $lines[] = "{$inner}{$keyPart} => ".var_export((string) $value, true).',';
        }
    }
    $lines[] = "{$pad}]";

    return implode("\n", $lines);
}

foreach ($localeSources as $locale => $source) {
    echo "Building {$locale}...\n";
    $data = loadValidationArray($source);
    $data['attributes'] = array_merge($data['attributes'] ?? [], $panelAttributes[$locale] ?? $panelAttributes['en']);
    $data['custom'] = $data['custom'] ?? [];

    $dir = "{$outDir}/{$locale}";
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents("{$dir}/validation.php", exportValidationPhp($data));
}

echo "Done. Files written to {$outDir}/{locale}/validation.php\n";
