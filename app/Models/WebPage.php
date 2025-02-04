<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebPage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'web_pages'; // Name of the table

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'page_id',
        'name',
        'slug',
        'title',
        'meta_description',
        'main',
        'og_title',
        'og_description',
        'og_img',
        'og_url',
        'embed_code_end',
        'embed_code_start',
        'website_id',
        'html',
        'css',
        'live',
    ];

    /**
     * Relationship: A web page belongs to a project (website).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'website_id', 'project_id');
    }

    /**
     * Determine if the page is the main page.
     *
     * @return bool
     */
    public function isMain()
    {
        return $this->main === 1;
    }

    
}
