<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * DomainNameAPI yalnızca .tr ailesi için; Spaceship global kalsın.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $trTlds = [
        '.tr',
        '.com.tr',
        '.net.tr',
        '.org.tr',
        '.gen.tr',
        '.biz.tr',
        '.info.tr',
        '.name.tr',
        '.web.tr',
        '.tv.tr',
        '.av.tr',
        '.dr.tr',
        '.edu.tr',
        '.bel.tr',
        '.pol.tr',
        '.k12.tr',
        '.gov.tr',
    ];

    public function up(): void
    {
        $now = now();

        DB::table('domain_registrars')
            ->where('api_name', 'domainnameapi')
            ->update([
                'is_enabled' => true,
                'display_name' => 'DomainNameAPI',
                'updated_at' => $now,
            ]);

        // Metunic .tr atamalarını DomainNameAPI'ye taşı (metunic kapalıysa satış kırılıyordu).
        DB::table('domain_tlds')
            ->where('registrar_api_name', 'metunic')
            ->update([
                'registrar_api_name' => 'domainnameapi',
                'updated_at' => $now,
            ]);

        $sort = 60;
        foreach ($this->trTlds as $tld) {
            $existing = DB::table('domain_tlds')->where('tld', $tld)->first();
            if ($existing) {
                DB::table('domain_tlds')->where('id', $existing->id)->update([
                    'registrar_api_name' => 'domainnameapi',
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('domain_tlds')->insert([
                    'tld' => $tld,
                    'register_price' => 0,
                    'renew_price' => 0,
                    'registrar_api_name' => 'domainnameapi',
                    'is_active' => true,
                    'sort_order' => $sort,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            $sort += 5;
        }
    }

    public function down(): void
    {
        DB::table('domain_tlds')
            ->whereIn('tld', ['.com.tr', '.net.tr', '.org.tr'])
            ->where('registrar_api_name', 'domainnameapi')
            ->update(['registrar_api_name' => 'metunic']);

        DB::table('domain_tlds')
            ->where('registrar_api_name', 'domainnameapi')
            ->whereNotIn('tld', ['.com.tr', '.net.tr', '.org.tr'])
            ->update(['registrar_api_name' => null]);
    }
};
