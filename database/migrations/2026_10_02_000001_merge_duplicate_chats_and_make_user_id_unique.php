<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Merge all duplicate chat records for each user
        $duplicateUserIds = DB::table('chats')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicateUserIds as $userId) {
            $userChats = DB::table('chats')
                ->where('user_id', $userId)
                ->orderByDesc('last_message_at')
                ->orderByDesc('id')
                ->get();

            if ($userChats->count() <= 1) {
                continue;
            }

            // Keep the most recent chat as primary
            $primaryChat = $userChats->first();
            $duplicateChatIds = $userChats->slice(1)->pluck('id')->all();

            // Move all messages from duplicate chats into the primary chat
            DB::table('chat_messages')
                ->whereIn('chat_id', $duplicateChatIds)
                ->update(['chat_id' => $primaryChat->id]);

            // If any duplicate was open, ensure primary is also open
            $hasOpen = $userChats->contains(fn ($c) => $c->status === 'open');
            if ($hasOpen) {
                DB::table('chats')
                    ->where('id', $primaryChat->id)
                    ->update(['status' => 'open']);
            }

            // Delete duplicate chat records
            DB::table('chats')
                ->whereIn('id', $duplicateChatIds)
                ->delete();
        }

        // 2. Add unique constraint on user_id in chats table
        Schema::table('chats', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });
    }
};
