<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'code',
        'stock',
        'condition',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Scope untuk menyaring barang yang siap dipinjam (stok > 0 dan kondisi baik)
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('stock', '>', 0)->where('condition', 'baik');
    }

    /**
     * Cek apakah barang siap dipinjam
     */
    public function isAvailable(): bool
    {
        return $this->stock > 0 && $this->condition === 'baik';
    }
}
