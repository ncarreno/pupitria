<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['id', 'area_id', 'ciclo', 'bloque', 'apartado', 'texto', 'orden'])]
class Saber extends Model
{
    protected $table = 'saberes';

    public $incrementing = false;

    protected $keyType = 'string';

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
