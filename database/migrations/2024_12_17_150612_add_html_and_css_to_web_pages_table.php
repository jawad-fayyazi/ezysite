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
    Schema::table('web_pages', function (Blueprint $table) {
        $table->longText('html'); // Large HTML content
        $table->longText('css');  // Large CSS content
    });
}


    /**
     * Reverse the migrations.
     */
public function down()
{
    Schema::table('web_pages', function (Blueprint $table) {
        $table->dropColumn(['html', 'css']);
    });
}

};
