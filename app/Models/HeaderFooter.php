<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeaderFooter extends Model
{
    use HasFactory;


    // Specify the table if it doesn't follow Laravel's naming convention
    protected $table = 'headers_footers';

    // Set which attributes are mass assignable (you can use other methods if needed)
    protected $fillable = [
        'website_id',
        'html',
        'css',
        'json',
        'is_header',
    ];


    // Optionally, you can add relationships (e.g., with the Project model)
    public function project()
    {
        return $this->belongsTo(Project::class, 'website_id', 'project_id');  // 'website_id' is foreign key, 'project_id' is the primary key
    }
}
