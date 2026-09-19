<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id')->index();
            $table->string('event_type')->index(); // view_product, add_to_cart, remove_from_cart, checkout_started, cart_abandoned
            $table->string('entity_type')->nullable(); // product, cart, order
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('payload')->nullable(); // prices, referrer, UTM source, items count
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_events');
    }
};
