<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempPage extends Model
{
    use HasFactory;

    // Define the table name (if it doesn't follow Laravel's pluralization convention)
    protected $table = 'temp_pages';

    // Define the primary key (if it's not 'id')
    protected $primaryKey = 'id';

    // Define the fillable fields (to allow mass assignment)
    protected $fillable = [
        'page_id',
        'name',
        'slug',
        'title',
        'meta_description',
        'main',
        'og',
        'embed_code_start',
        'embed_code_end',
        'template_id',
        'html',
        'css'
    ];

    // Define the relationship with the Template model
    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }
}
