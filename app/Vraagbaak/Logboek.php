<?php

namespace App\Vraagbaak;

use Illuminate\Database\Eloquent\Model;

class Logboek extends Model
{
    protected $table = 'vraagbaak_logboek';
    protected $fillable = ['user_id', 'vraag', 'herkend_als', 'status', 'parameters', 'duur_ms', 'feedback'];
    protected $casts = ['parameters' => 'array'];

    public function user() { return $this->belongsTo(\App\Models\User::class); }
}
