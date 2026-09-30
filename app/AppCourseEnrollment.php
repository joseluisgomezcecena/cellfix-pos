<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppCourseEnrollment extends Model
{
    public $timestamps = false;

    protected $fillable = ['course_id', 'contact_id', 'enrolled_at'];

    protected $casts = [
        'enrolled_at' => 'datetime',
    ];

    public function course()
    {
        return $this->belongsTo(AppCourse::class, 'course_id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
}
