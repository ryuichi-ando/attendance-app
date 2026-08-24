<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'break_id',
        'requested_break_start',
        'requested_break_end',
        'status',
    ];

    public function break()
    {
        return $this->belongsTo(AttendanceBreak::class);
    }
}
