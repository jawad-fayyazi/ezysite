<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveColumnFromTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Delete all rows of data in the templates table
        DB::table('templates')->delete();

        // Remove the column from the templates table
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('template_image'); // Replace with your column name
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // If you want to restore the column and the data, you can reinsert the data (if required)
        Schema::table('templates', function (Blueprint $table) {
            $table->text('template_image')->nullable(); // Replace with the column you want to add back
        });

        // Optional: Restore the rows (if you had backups or want to reinsert data)
        // Example: DB::table('templates')->insert([...]);

        // If you want to restore data, you can manually reinsert rows here.
    }
}
