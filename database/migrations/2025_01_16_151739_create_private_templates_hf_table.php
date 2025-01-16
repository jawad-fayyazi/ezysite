<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrivateTemplatesHfTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('private_templates_hf', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('private_template_id'); // The foreign key for private_templates table
            $table->unsignedBigInteger('user_id'); // The foreign key for users table
            $table->longText('json')->nullable(); // JSON column for data
            $table->longText('html')->nullable(); // HTML content for header/footer
            $table->longText('css')->nullable(); // CSS content for header/footer
            $table->timestamps();

            // Foreign key for private_template_id, referencing the private_templates table
            $table->foreign('private_template_id')
                  ->references('id')
                  ->on('private_templates') // Assuming this table exists
                  ->onDelete('cascade'); // When a template is deleted, remove related entries

            // Foreign key for user_id, referencing the users table
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users') // Assuming the users table exists
                  ->onDelete('cascade'); // When a user is deleted, remove related entries
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('private_templates_hf');
    }
}
