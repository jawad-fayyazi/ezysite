<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMainpageidToProjectsData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects_data', function (Blueprint $table) {
            $table->integer('mainpageid')->nullable()->after('project_name'); // Adds the 'mainpageid' column
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects_data', function (Blueprint $table) {
            $table->dropColumn('mainpageid'); // Drops the 'mainpageid' column if the migration is rolled back
        });
    }
}
