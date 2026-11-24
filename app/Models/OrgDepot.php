<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrgDepot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['area_id', 'name', 'number', 'email', 'city', 'sort_order',
        'departments', 'street', 'postal_code', 'phone', 'country'];

    protected $casts = ['departments' => 'array'];

    /** Afdelingen van deze vestiging (slugs uit config('boels.afdelingen')); leeg = nog niet ingesteld. */
    public function afdelingen(): array
    {
        $geldig = array_keys(config('boels.afdelingen', []));

        return array_values(array_intersect($geldig, (array) ($this->departments ?? [])));
    }

    public function heeftAfdeling(string $slug): bool
    {
        return in_array($slug, $this->afdelingen(), true);
    }

    /** Gegevens voor de API's (/api/infrastructure en /api/internal/infrastructure). */
    public function apiGegevens(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'number' => $this->number,          // "759" of "384, 769" (depotnummers uit het ERP)
            'numbers' => $this->numbers(),
            'email' => $this->email,
            'city' => $this->city,
            'departments' => $this->afdelingen(),
            'departments_set' => $this->departments !== null,
            'street' => $this->street,
            'postal_code' => $this->postal_code,
            'phone' => $this->phone,
            'country' => $this->country,
        ];
    }

    /** Depotnummers als lijst ("384, 769" → ['384', '769']). */
    public function numbers(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->number))));
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(OrgArea::class, 'area_id');
    }
}
