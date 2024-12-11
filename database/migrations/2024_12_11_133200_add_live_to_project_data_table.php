<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('projects_data', function (Blueprint $table) {
        $table->boolean('live')->default(false); // Add a live column, default to false
    });
}

public function down()
{
    Schema::table('projects_data', function (Blueprint $table) {
        $table->dropColumn('live');
    });
}



};
