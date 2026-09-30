<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppCourse extends Model
{
    protected $fillable = [
        'business_id', 'title', 'description', 'image_path',
        'target_location_id', 'instructor_name',
        'starts_at', 'ends_at', 'capacity', 'is_active',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'is_active' => 'boolean',
        'capacity'  => 'integer',
    ];

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'target_location_id');
    }

    public function enrollments()
    {
        return $this->hasMany(AppCourseEnrollment::class, 'course_id');
    }

    /**
     * ¿Todavía está por comenzar? Se usa para permitir inscripciones.
     */
    public function hasNotStarted(): bool
    {
        return $this->starts_at && $this->starts_at->isFuture();
    }

    /**
     * ¿Ya se llenó la capacidad? capacity=0 significa ilimitado.
     */
    public function isFull(): bool
    {
        if ((int) $this->capacity === 0) return false;
        return $this->enrollments()->count() >= (int) $this->capacity;
    }

    public function spotsLeft(): ?int
    {
        if ((int) $this->capacity === 0) return null; // ilimitado
        return max(0, (int) $this->capacity - $this->enrollments()->count());
    }
}
