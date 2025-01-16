<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('private_temp_pages', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['template_private_id']);
            
            // Drop the old column
            $table->dropColumn('template_private_id');
            
            // Add the new column with the correct name
            $table->bigInteger('private_template_id')->unsigned()->nullable(false);
            
            // Recreate the foreign key constraint
            $table->foreign('private_template_id')
                ->references('id')
                ->on('private_templates')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('private_temp_pages', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['private_template_id']);
            
            // Drop the new column
            $table->dropColumn('private_template_id');
            
            // Add the old column back
            $table->bigInteger('template_private_id')->unsigned()->nullable(false);
            
            // Recreate the foreign key constraint
            $table->foreign('template_private_id')
                ->references('id')
                ->on('private_templates')
                ->onDelete('cascade');
        });
    }
};
