<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;  // Add the HasFactory import

class Project extends Model
{
    use HasFactory;  // Use the HasFactory trait

    protected $table = 'projects_data';  // Name of the table
    protected $primaryKey = 'project_id';  // Primary key of the table
    public $timestamps = false;  // If you don't have timestamps in your table

    // Fillable properties (allowed to be mass-assigned)
    protected $fillable = [
        'project_id',  // Keep project_id in fillable, but since it's primary key, you might not need to mass-assign it
        'project_name', // project_name should be fillable
        'project_json', // project_json should also be fillable to update it
        'user_id',
        'domain',
        'description',
        'live',
        'robots_txt',
        'header_embed',
        'footer_embed',
        'ss',
        'favicon',
    ];

    // Cast the project_json column to an array
    protected $casts = [
        'project_json' => 'array',  // Automatically convert project_json to an array when accessed
    ];


    // Inside Project model

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function pages()
    {
        return $this->hasMany(WebPage::class, 'website_id');
    }


}
