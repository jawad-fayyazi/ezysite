<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateHeaderFooter extends Model
{
    use HasFactory;

    // Define the table name (if it doesn't follow Laravel's pluralization convention)
    protected $table = 'templates_headers_footers';

    // Define the primary key (if it's not 'id')
    protected $primaryKey = 'id';

    // Define the fillable fields (to allow mass assignment)
    protected $fillable = [
        'template_id',
        'json',
        'html',
        'css',
        'is_header'
    ];

    // Define the relationship with the Template model
    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }
}
