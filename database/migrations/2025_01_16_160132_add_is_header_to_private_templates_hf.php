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
        Schema::table('private_templates_hf', function (Blueprint $table) {
	        $table->boolean('is_header')->default(1)->after('css');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('private_templates_hf', function (Blueprint $table) {
       
            $table->dropColumn('is_header');
 

    //
        });
    }
};
