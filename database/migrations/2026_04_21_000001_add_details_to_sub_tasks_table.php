<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sub_tasks', function (Blueprint $table) {
            $table->string('unique_code')->nullable()->after('task_id');
            $table->text('description')->nullable()->after('title');
            $table->date('start_date')->nullable()->after('description');
            $table->date('end_date')->nullable()->after('start_date');
            $table->unique('unique_code');
        });

        DB::table('sub_tasks')->orderBy('id')->get(['id'])->each(function ($subTask) {
            DB::table('sub_tasks')
                ->where('id', $subTask->id)
                ->update(['unique_code' => 'ST-LEGACY-' . str_pad((string) $subTask->id, 6, '0', STR_PAD_LEFT)]);
        });
    }

    public function down(): void
    {
        Schema::table('sub_tasks', function (Blueprint $table) {
            $table->dropUnique(['unique_code']);
            $table->dropColumn(['unique_code', 'description', 'start_date', 'end_date']);
        });
    }
};
