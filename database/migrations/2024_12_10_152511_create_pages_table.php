<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('web_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_id');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('title')->nullable();
            $table->text('meta_description')->nullable();
            $table->integer('main')->default(0);
            $table->text('og')->nullable();
            $table->text('embed_code_start')->nullable();
            $table->text('embed_code_end')->nullable();
            $table->Integer('website_id');
            $table->foreign('website_id')->references('project_id')->on('projects_data')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('web_pages');
    }
}
