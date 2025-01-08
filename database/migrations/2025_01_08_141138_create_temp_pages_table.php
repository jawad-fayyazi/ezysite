<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTempPagesTable extends Migration
{
    public function up()
    {
        Schema::create('temp_pages', function (Blueprint $table) {
            $table->id();  // 'id' column as bigint unsigned and auto-incremented
            $table->string('page_id');  // 'page_id' column as varchar(191)
            $table->string('name');  // 'name' column as varchar(191)
            $table->string('slug')->nullable();  // 'slug' column as varchar(191), nullable
            $table->string('title')->nullable();  // 'title' column as varchar(191), nullable
            $table->text('meta_description')->nullable();  // 'meta_description' column as text, nullable
            $table->boolean('main')->default(0);  // 'main' column as tinyint(1), default = 0
            $table->text('og')->nullable();  // 'og' column as text, nullable
            $table->text('embed_code_start')->nullable();  // 'embed_code_start' column as text, nullable
            $table->text('embed_code_end')->nullable();  // 'embed_code_end' column as text, nullable
            $table->integer('template_id');  // 'template_id' as int (foreign key)
            $table->timestamps();  // 'created_at' and 'updated_at' columns
            $table->longText('html');  // 'html' column as longtext
            $table->longText('css');  // 'css' column as longtext

            // Foreign key constraint
            $table->foreign('template_id')->references('template_id')->on('templates')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('temp_pages');
    }
}
