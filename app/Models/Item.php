<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Item extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'title',
        'category_id',
        'price',
        'currency_id',
        'available_quantity',
        'buying_mode',
        'listing_type_id',
        'condition',
        'description',
        'video_id',
        'pictures',
        'mercadolibre_id',
        'status',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'pictures' => 'array',
        'price' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    /**
     * Scope a query to only include published items.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to only include draft items.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Mark item as published.
     */
    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /**
     * Mark item as draft.
     */
    public function markAsDraft(): void
    {
        $this->update([
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Mark item as sold.
     */
    public function markAsSold(): void
    {
        $this->update([
            'status' => 'sold',
        ]);
    }

    /**
     * Check if item is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if item is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if item is sold.
     */
    public function isSold(): bool
    {
        return $this->status === 'sold';
    }

    /**
     * Get formatted price with currency.
     */
    public function getFormattedPriceAttribute(): string
    {
        return $this->currency_id . ' ' . number_format($this->price, 2);
    }

    /**
     * Get first picture URL.
     */
    public function getFirstPictureUrlAttribute(): ?string
    {
        return $this->pictures[0]['source'] ?? null;
    }

    /**
     * Add picture to item.
     */
    public function addPicture(string $source): void
    {
        $pictures = $this->pictures ?? [];
        $pictures[] = ['source' => $source];
        $this->update(['pictures' => $pictures]);
    }

    /**
     * Remove picture from item.
     */
    public function removePicture(int $index): void
    {
        $pictures = $this->pictures ?? [];
        if (isset($pictures[$index])) {
            unset($pictures[$index]);
            $this->update(['pictures' => array_values($pictures)]);
        }
    }

    /**
     * Get MercadoLibre API data format.
     */
    public function toMercadoLibreFormat(): array
    {
        return [
            'title' => $this->title,
            'category_id' => $this->category_id,
            'price' => $this->price,
            'currency_id' => $this->currency_id,
            'available_quantity' => $this->available_quantity,
            'buying_mode' => $this->buying_mode,
            'listing_type_id' => $this->listing_type_id,
            'condition' => $this->condition,
            'description' => [
                'plain_text' => $this->description
            ],
            'video_id' => $this->video_id,
            'pictures' => $this->pictures,
        ];
    }
}
