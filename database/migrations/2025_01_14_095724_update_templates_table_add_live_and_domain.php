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
        Schema::table('templates', function (Blueprint $table) {
            $table->boolean('live')->default(false); // Add 'live' column, default is false
            $table->string('domain', 255)->nullable(); // Add 'domain' column, nullable
            $table->dropColumn('template_preview_link'); // Remove 'template_preview_link' column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('live'); // Remove 'live' column
            $table->dropColumn('domain'); // Remove 'domain' column
            $table->string('template_preview_link', 255)->nullable(); // Re-add 'template_preview_link' column
        });
    }
};
