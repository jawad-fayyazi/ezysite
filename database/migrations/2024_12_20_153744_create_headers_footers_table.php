<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */


public function up()
{
    Schema::create('headers_footers', function (Blueprint $table) {
        $table->id();
        $table->integer('website_id'); // Ensure website_id is signed to match project_id type
        $table->foreign('website_id')->references('project_id')->on('projects_data')->onDelete('cascade'); // Foreign key reference to project_id
        $table->longText('json'); // Stores the GrapesJS JSON data for header/footer
        $table->longText('html'); // Stores the HTML content
        $table->longText('css'); // Stores the CSS content
        $table->timestamps();
    });
}

public function down()
{
    Schema::dropIfExists('headers_footers');
}



};
