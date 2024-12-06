<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Template extends Model
{
    use HasFactory;

    // The table associated with the model
    protected $table = 'templates';
    protected $primaryKey = 'template_id'; // Define the primary key column name


    // The attributes that are mass assignable
    protected $fillable = [
        'template_name',
        'template_category_id',
        'template_description',
        'template_json',
        'template_image',
        'template_preview_link',
        // Add other columns that may exist in your templates table
    ];

    // You can define any relationships here if applicable
    // Example: A template may belong to a user (if applicable)
    // public function user() {
    //     return $this->belongsTo(User::class);
    // }

    public function category()
    {
        return $this->belongsTo(TemplateCategory::class, 'template_category_id');
    }
}
