<?php

namespace App\Models;

use TurboFrame\Database\Model;

class Post extends Model
{
    protected string $table = 'posts';

    protected string $primaryKey = 'id';

    protected array $fillable = [];

    protected array $hidden = [];

    protected array $casts = [];
}