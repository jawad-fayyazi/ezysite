<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplatesHeadersFootersTable extends Migration
{
    public function up()
    {
        Schema::create('templates_headers_footers', function (Blueprint $table) {
            $table->id();  // 'id' column as bigint unsigned and auto-incremented
            $table->integer('template_id');  // foreign key to the 'templates' table
            $table->longText('json');  // 'json' column to store JSON data
            $table->longText('html');  // 'html' column to store HTML data
            $table->longText('css');  // 'css' column to store CSS data
            $table->timestamps();  // 'created_at' and 'updated_at' columns
            $table->boolean('is_header')->default(1);  // 'is_header' column to indicate if it's header (default = 1)

            // Foreign key constraint
            $table->foreign('template_id')->references('template_id')->on('templates')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('templates_headers_footers');
    }
}
