<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RatingSubmission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'rateable_type',
        'rateable_id',
        'rating',
        'category',
        'comment',
        'rater_id',
        'submission_context_type',
        'submission_context_id',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function rateable(): MorphTo
    {
        return $this->morphTo();
    }

    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function context(): MorphTo
    {
        return $this->morphTo(null, 'submission_context_type', 'submission_context_id');
    }
}
