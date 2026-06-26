<?php

namespace Database\Seeders;

use App\Models\NavMenuItem;
use Illuminate\Database\Seeder;

/**
 * Yasal sayfa içeriklerini ve SSS menü bağlantısını canlıya senkronlar.
 */
class SyncLegalPagesSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LegalSitePagesSeeder::class);
        $this->call(LegalNavFooterSeeder::class);

        NavMenuItem::query()
            ->where('href', '/#faq')
            ->update(['href' => '/p/sss']);
    }
}
