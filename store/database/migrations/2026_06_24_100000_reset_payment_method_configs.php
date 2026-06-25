<?php

use App\Filament\Resources\PaymentMethods\Schemas\PaymentMethodForm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('payment_methods')->select('id', 'code')->get() as $row) {
            $config = PaymentMethodForm::defaultConfig((string) $row->code);

            DB::table('payment_methods')
                ->where('id', $row->id)
                ->update(['config' => json_encode($config, JSON_THROW_ON_ERROR)]);
        }
    }

    public function down(): void
    {
        //
    }
};
