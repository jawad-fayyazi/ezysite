<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrivateTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('private_templates', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('template_name'); // Template name column
            $table->text('description')->nullable(); // Template description column (nullable)
            $table->longText('template_json'); // Use LONGTEXT column to store template JSON data (for large data)
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Foreign key linking to the users table
            $table->timestamps(); // Timestamps for created_at and updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('private_templates');
    }
}
