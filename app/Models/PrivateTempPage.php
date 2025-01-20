<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivateTempPage extends Model
{
    use HasFactory;

    protected $table = 'private_temp_pages'; // Table name

    protected $fillable = [
        'page_id',
        'private_template_id',
        'user_id',
        'name',
        'slug',
        'title',
        'meta_description',
        'main',
        'og_title',
        'og_description',
        'og_img',
        'og_url',
        'embed_code_start',
        'embed_code_end',
        'html',
        'css',
    ];

    /**
     * Relationship with the PrivateTemplate model.
     */
    public function privateTemplate()
    {
        return $this->belongsTo(PrivateTemplate::class, 'private_template_id');
    }

    /**
     * Relationship with the User model.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
