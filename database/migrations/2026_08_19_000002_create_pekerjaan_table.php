<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pekerjaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('nama_projek');
            $table->text('pekerjaan');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        DB::table('conversations')
            ->join('users', 'conversations.user_id', '=', 'users.id')
            ->where('conversations.status', 'completed')
            ->whereNotNull('conversations.metadata')
            ->select([
                'conversations.user_id',
                'users.name',
                'conversations.metadata',
                'conversations.created_at',
                'conversations.updated_at',
            ])
            ->get()
            ->each(function ($conversation): void {
                $metadata = json_decode($conversation->metadata, true) ?? [];
                $activity = $metadata['daily_activity'] ?? null;

                if (!$activity || empty($activity['project_company']) || empty($activity['summary'])) {
                    return;
                }

                DB::table('pekerjaan')->insert([
                    'user_id' => $conversation->user_id,
                    'name' => $conversation->name,
                    'nama_projek' => $activity['project_company'],
                    'pekerjaan' => $activity['summary'],
                    'created_at' => $conversation->created_at,
                    'updated_at' => $conversation->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('pekerjaan');
    }
};
