<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
public function up()
    {
        Schema::create('template_categories', function (Blueprint $table) {
            $table->id(); // Auto-incrementing ID field
            $table->string('name'); // Name of the category
            $table->text('description')->nullable(); // Description of the category
            $table->timestamps(); // Created_at and updated_at timestamps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('template_categories');
    }
}
