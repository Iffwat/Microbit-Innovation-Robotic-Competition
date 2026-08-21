<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnockoutSlot extends Model
{
    protected $fillable = [
        'category_id', 'bracket', 'round', 'round_order',
        'slot_number', 'team_id', 'match_id', 'is_bye',
    ];

    protected $casts = [
        'is_bye'       => 'boolean',
        'round_order'  => 'integer',
        'slot_number'  => 'integer',
    ];

    // Round labels in Malay
    public static array $roundLabels = [
        'R32'   => 'Pusingan 32',
        'R16'   => 'Pusingan 16',
        'QF'    => 'Suku Akhir',
        'SF'    => 'Semi Akhir',
        '3rd'   => 'Tempat Ke-3',
        'Final' => 'Penentuan Juara',
    ];

    public static array $roundOrder = [
        'R32' => 1, 'R16' => 2, 'QF' => 3, 'SF' => 4, '3rd' => 5, 'Final' => 6,
    ];

    public function getRoundLabelAttribute(): string
    {
        return self::$roundLabels[$this->round] ?? $this->round;
    }

    public function getBracketLabelAttribute(): string
    {
        return match($this->bracket) {
            'trophy' => '🏆 Trofi',
            'cup'    => '🥈 Piala',
            default  => $this->bracket,
        };
    }

    // Relations
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'match_id');
    }
}
