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
        Schema::table('projects_data', function (Blueprint $table) {
            //
		$table->string('ss')->nullable()->after('project_name'); // Adjust position as needed
        	$table->string('favicon')->nullable()->after('ss'); // Adjust position as needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects_data', function (Blueprint $table) {
            //
        });
    }
};
