<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Our own record of every private chat that ever talked to the bot, with
 * its approval status, bound panel user and active project. Existing
 * bindings are copied from Telegraph's table, which is left in place.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_chats', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('chat_id')->unique();
            $table->bigInteger('telegram_user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->string('hash', 50)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('pending')->index();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        if (! Schema::hasTable('telegraph_chats')) {
            return;
        }

        $users = DB::table('users')->pluck('id')->all();

        foreach (DB::table('telegraph_chats')->orderBy('id')->get() as $chat) {
            if (! is_numeric($chat->chat_id) || (int) $chat->chat_id < 0) {
                continue; // channels and groups were never users of the bot
            }

            $userId = in_array($chat->user_id, $users) ? $chat->user_id : null;

            DB::table('bot_chats')->insertOrIgnore([
                'chat_id'          => (int) $chat->chat_id,
                'telegram_user_id' => (int) $chat->chat_id,
                'name'             => $chat->name,
                'hash'             => $chat->hash,
                'user_id'          => $userId,
                'status'           => $userId ? 'active' : 'pending',
                'created_at'       => $chat->created_at ?? now(),
                'updated_at'       => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_chats');
    }
};
