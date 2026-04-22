<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('unique_id')->nullable()->after('id');
            $table->unique('unique_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->string('unique_id')->nullable()->after('id');
            $table->unique('unique_id');
        });

        $generateProjectId = function (): string {
            do {
                $candidate = 'PRJ-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            } while (DB::table('projects')->where('unique_id', $candidate)->exists());

            return $candidate;
        };

        $generateTaskId = function (): string {
            do {
                $candidate = 'TSK-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            } while (DB::table('tasks')->where('unique_id', $candidate)->exists());

            return $candidate;
        };

        DB::table('projects')
            ->whereNull('unique_id')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($project) use ($generateProjectId) {
                DB::table('projects')
                    ->where('id', $project->id)
                    ->update(['unique_id' => $generateProjectId()]);
            });

        DB::table('tasks')
            ->whereNull('unique_id')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($task) use ($generateTaskId) {
                DB::table('tasks')
                    ->where('id', $task->id)
                    ->update(['unique_id' => $generateTaskId()]);
            });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropUnique(['unique_id']);
            $table->dropColumn('unique_id');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['unique_id']);
            $table->dropColumn('unique_id');
        });
    }
};
