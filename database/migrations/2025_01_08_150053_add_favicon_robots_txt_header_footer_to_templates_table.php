<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFaviconRobotsTxtHeaderFooterToTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('templates', function (Blueprint $table) {
            // Add new columns
            $table->string('favicon', 191)->nullable()->after('template_category_id');
            $table->text('robots_txt')->nullable()->after('favicon');
            $table->text('header_embed')->nullable()->after('robots_txt');
            $table->text('footer_embed')->nullable()->after('header_embed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('templates', function (Blueprint $table) {
            // Drop the columns if the migration is rolled back
            $table->dropColumn(['favicon', 'robots_txt', 'header_embed', 'footer_embed']);
        });
    }
}
