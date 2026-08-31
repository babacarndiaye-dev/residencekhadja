<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftTemplate extends Model
{
    protected $guarded = ['id'];

    public function durationMinutes(): int
    {
        [$sh, $sm] = array_map('intval', explode(':', substr($this->start_time, 0, 5)));
        [$eh, $em] = array_map('intval', explode(':', substr($this->end_time, 0, 5)));
        $start = $sh * 60 + $sm;
        $end = $eh * 60 + $em;
        if ($end <= $start) {
            $end += 24 * 60; // shift de nuit
        }

        return $end - $start - $this->break_minutes;
    }
}
