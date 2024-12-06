<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivateTemplate extends Model
{
    use HasFactory;

    // Specify the table name (if it's not the plural form of the model name)
    protected $table = 'private_templates';

    // Allow mass assignment for the following fields
    protected $fillable = [
        'template_name',
        'description',
        'template_json',
        'user_id', // The user ID who created the template
    ];

    protected $casts = [
        'template_json' => 'array', // Automatically cast template_json to an array
    ];

    // Define the relationship to the user (assuming the user has many private templates)
    public function user()
    {
        return $this->belongsTo(User::class); // Adjust the User model's namespace if it's different
    }
}
