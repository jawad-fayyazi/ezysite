kk<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyPrivateTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('private_templates', function (Blueprint $table) {
            // Add new columns
            $table->string('favicon', 191)->nullable()->after('template_json');
            $table->text('robots_txt')->nullable()->after('favicon');
            $table->text('header_embed')->nullable()->after('robots_txt');
            $table->text('footer_embed')->nullable()->after('header_embed');

            // Modify the existing column template_json
            $table->longText('template_json')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('private_templates', function (Blueprint $table) {
            // Drop the newly added columns
            $table->dropColumn('favicon');
            $table->dropColumn('robots_txt');
            $table->dropColumn('header_embed');
            $table->dropColumn('footer_embed');

            // Revert the change to template_json
            $table->longText('template_json')->nullable(false)->change();
        });
    }
}

