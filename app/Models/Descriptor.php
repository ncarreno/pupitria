<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['normativa_id', 'codigo', 'competencia_clave', 'competencia_clave_nombre', 'texto'])]
class Descriptor extends Model
{
    protected $table = 'descriptores';

    public function normativa(): BelongsTo
    {
        return $this->belongsTo(Normativa::class);
    }
}
