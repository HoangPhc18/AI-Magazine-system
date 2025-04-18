<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookPost extends Model
{
    protected $fillable = [
        'content',
        'source_url',
        'page_or_group_name',
        'processed'
    ];
}
