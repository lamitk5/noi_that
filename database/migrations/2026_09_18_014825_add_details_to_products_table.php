<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('material')->nullable()->after('description');
            $table->string('dimensions')->nullable()->after('material');
            $table->string('color')->nullable()->after('dimensions');
            $table->decimal('weight', 8, 2)->nullable()->after('color');
            $table->boolean('is_featured')->default(false)->after('base_price');
            $table->unsignedInteger('views_count')->default(0)->after('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'material',
                'dimensions',
                'color',
                'weight',
                'is_featured',
                'views_count',
            ]);
        });
    }
};
