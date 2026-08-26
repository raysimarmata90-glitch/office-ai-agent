<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->unique()->constrained('messages')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('sender_type', ['ai', 'user']);
            $table->text('content');
            $table->unsignedInteger('step_number');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });

        DB::table('messages')
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->select([
                'messages.id as message_id',
                'messages.conversation_id',
                'conversations.user_id',
                'messages.sender_type',
                'messages.content',
                'messages.step_number',
                'messages.metadata',
                'messages.created_at',
                'messages.updated_at',
            ])
            ->orderBy('messages.id')
            ->get()
            ->each(function ($message): void {
                DB::table('chat_histories')->insert([
                    'message_id' => $message->message_id,
                    'conversation_id' => $message->conversation_id,
                    'user_id' => $message->user_id,
                    'sender_type' => $message->sender_type,
                    'content' => $message->content,
                    'step_number' => $message->step_number,
                    'metadata' => $message->metadata,
                    'created_at' => $message->created_at,
                    'updated_at' => $message->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_histories');
    }
};
