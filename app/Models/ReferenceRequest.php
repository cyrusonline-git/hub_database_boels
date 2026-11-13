<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** PO-bewaking: één regel per keer dat de klant om een referentie/PO-nummer is gevraagd. */
class ReferenceRequest extends Model
{
    protected $table = 'reference_requests';

    protected $fillable = ['contract_number', 'gevraagd_aan', 'opmerking', 'user_id', 'gevraagd_op'];

    protected $casts = ['gevraagd_op' => 'date:Y-m-d'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
