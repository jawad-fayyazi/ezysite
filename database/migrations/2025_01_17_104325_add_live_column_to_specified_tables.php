<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLiveColumnToSpecifiedTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add 'live' column to web_pages
        Schema::table('web_pages', function (Blueprint $table) {
            $table->boolean('live')->default(false); // Add at the end
        });

        // Add 'live' column to temp_pages
        Schema::table('temp_pages', function (Blueprint $table) {
            $table->boolean('live')->default(false); // Add at the end
        });

        // Add 'live' column to templates_headers_footers
        Schema::table('templates_headers_footers', function (Blueprint $table) {
            $table->boolean('live')->default(false); // Add at the end
        });

        // Add 'live' column to headers_footers
        Schema::table('headers_footers', function (Blueprint $table) {
            $table->boolean('live')->default(false); // Add at the end
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove 'live' column from web_pages
        Schema::table('web_pages', function (Blueprint $table) {
            $table->dropColumn('live');
        });

        // Remove 'live' column from temp_pages
        Schema::table('temp_pages', function (Blueprint $table) {
            $table->dropColumn('live');
        });

        // Remove 'live' column from templates_headers_footers
        Schema::table('templates_headers_footers', function (Blueprint $table) {
            $table->dropColumn('live');
        });

        // Remove 'live' column from headers_footers
        Schema::table('headers_footers', function (Blueprint $table) {
            $table->dropColumn('live');
        });
    }
}
