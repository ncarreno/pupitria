<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['codigo', 'comunidad', 'etapa', 'referencia', 'fuente', 'activa'])]
class Normativa extends Model
{
    protected function casts(): array
    {
        return ['activa' => 'boolean'];
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class)->orderBy('orden');
    }

    public function descriptores(): HasMany
    {
        return $this->hasMany(Descriptor::class);
    }
}
