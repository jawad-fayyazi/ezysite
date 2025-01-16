<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivateTemplateHf extends Model
{
    use HasFactory;

    protected $table = 'private_templates_hf'; // Table name

    protected $fillable = [
        'private_template_id',
        'user_id',
        'json',
        'html',
        'css',
        'is_header'
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
