k<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRobotsTxtAndEmbedCodesToProjectsData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects_data', function (Blueprint $table) {
            // Adding robots.txt column
            $table->text('robots_txt')->nullable()->after('description'); // Adjusting the position after the description column

            // Adding header embed code column
            $table->text('header_embed')->nullable()->after('robots_txt'); // Adjusting the position

            // Adding footer embed code column
            $table->text('footer_embed')->nullable()->after('header_embed'); // Adjusting the position
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
            // Dropping the columns if migration is rolled back
            $table->dropColumn(['robots_txt', 'header_embed', 'footer_embed']);
        });
    }
}

