<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
public function up()
    {
        Schema::table('templates', function (Blueprint $table) {
            // Add the template_category_id column
            $table->unsignedBigInteger('template_category_id');

            // Set the foreign key constraint
            $table->foreign('template_category_id')
                ->references('id')
                ->on('template_categories')
                ->onDelete('cascade');
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
            // Drop the foreign key and column if rolled back
            $table->dropForeign(['template_category_id']);
            $table->dropColumn('template_category_id');
        });
    }
}
