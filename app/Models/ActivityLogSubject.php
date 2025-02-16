<?php

namespace Convoy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityLogSubject extends Model
{
    use HasFactory;

    public function activityLog()
    {
        return $this->belongsTo(ActivityLog::class);
    }

    public function subject()
    {
        $morph = $this->morphTo();
        if (method_exists($morph, 'withTrashed')) {
            return $morph->withTrashed();
        }

        return $morph;
    }
}
