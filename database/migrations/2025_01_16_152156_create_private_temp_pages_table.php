<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrivateTempPagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('private_temp_pages', function (Blueprint $table) {
            $table->id();
            $table->string('page_id', 191)->nullable();
            $table->string('name', 191)->nullable();
            $table->string('slug', 191)->nullable();
            $table->string('title', 191)->nullable();
            $table->text('meta_description')->nullable();
            $table->tinyInteger('main')->default(0);
            $table->text('og')->nullable();
            $table->text('embed_code_start')->nullable();
            $table->text('embed_code_end')->nullable();
            $table->unsignedBigInteger('template_private_id'); // Foreign key to private_templates table
            $table->unsignedBigInteger('user_id'); // Foreign key to users table
            $table->timestamps();

            $table->longText('html')->nullable();
            $table->longText('css')->nullable();

            // Foreign key for template_private_id, referencing the private_templates table
            $table->foreign('template_private_id')
                  ->references('id')
                  ->on('private_templates') // Assuming this table exists
                  ->onDelete('cascade'); // When a template is deleted, remove related pages

            // Foreign key for user_id, referencing the users table
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users') // Assuming the users table exists
                  ->onDelete('cascade'); // When a user is deleted, remove related pages
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('private_temp_pages');
    }
}
