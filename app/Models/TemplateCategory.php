<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateCategory extends Model
{
    use HasFactory;

    protected $table = 'template_categories'; // Define the table name if it's not the default plural form

    protected $fillable = [
        'name',
        'description',
    ];

    // Define the relationship with Template model (one-to-many)
    public function templates()
    {
        return $this->hasMany(Template::class, 'template_category_id');
    }
}
