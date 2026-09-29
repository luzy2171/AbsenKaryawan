<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('machine_status')
            ->where(function ($query) {
                $query->whereIn('machine_name', ['Solution C100X', 'Solution C100-C'])
                    ->orWhereIn('machine_type', ['solution', 'x100c'])
                    ->orWhereNull('machine_type');
            })
            ->update(['machine_type' => 'solution']);

        DB::table('machine_status')
            ->where(function ($query) {
                $query->whereIn('machine_name', ['Solution C100X', 'Solution C100-C'])
                    ->orWhereIn('machine_type', ['solution', 'x100c'])
                    ->orWhereNull('machine_type');
            })
            ->where(function ($query) {
                $query->where('port', 80)->orWhereNull('port');
            })
            ->update(['port' => 4370]);

        DB::table('machine_status')
            ->whereIn('machine_name', ['Solution C100X', 'Solution C100-C'])
            ->update(['machine_name' => 'Solution X100C']);

        $hasDefault = DB::table('machine_status')->where('is_default', true)->exists();
        if (!$hasDefault) {
            $firstMachine = DB::table('machine_status')->orderBy('id')->value('id');
            if ($firstMachine) {
                DB::table('machine_status')->where('id', $firstMachine)->update(['is_default' => true]);
            }
        }
    }

    public function down(): void
    {
    }
};
