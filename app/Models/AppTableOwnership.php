<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppTableOwnership extends Model
{
    use HasFactory;

    protected $table = 'app_table_ownership';

    protected $fillable = ['table_name', 'owner_slug', 'owner_name', 'notes', 'locked'];

    protected $casts = ['locked' => 'boolean'];
}
