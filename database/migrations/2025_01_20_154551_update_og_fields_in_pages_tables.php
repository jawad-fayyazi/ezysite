<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateOgFieldsInPagesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tables = ['web_pages', 'temp_pages', 'private_temp_pages'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                // Add new columns
                $table->string('og_description')->nullable();
                $table->string('og_img')->nullable();
                $table->string('og_url')->nullable();

                // Rename the existing 'og' column to 'og_title'
                $table->renameColumn('og', 'og_title');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tables = ['web_pages', 'temp_pages', 'private_temp_pages'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                // Drop the new columns
                $table->dropColumn(['og_description', 'og_img', 'og_url']);

                // Rename 'og_title' back to 'og'
                $table->renameColumn('og_title', 'og');
            });
        }
    }
}
