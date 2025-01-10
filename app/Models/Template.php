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
        'favicon',
        'robots_txt',
        'header_embed',
        'footer_embed',
        'is_publish',
        'ss',
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

    public function tempPages()
    {
        return $this->hasMany(TempPage::class, 'template_id');
    }

    /**
     * Define the one-to-many relationship with the TemplateHeaderFooter model
     */
    public function templateHeaderFooters()
    {
        return $this->hasMany(TemplateHeaderFooter::class, 'template_id');
    }
}
