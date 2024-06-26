<?php

namespace App\Models;

use App\Models\Schedules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subject extends Model
{
    use HasFactory;

    protected $table = 'subjects';

    protected $fillable = [
        'Subject_Code',
        'Description',
        'Lec',
        'Lab',
        'Units',
        'Pre_Req',
        'Year_Level',
        'Semester',
        'College',
        'Department',
        'Program',
        'Academic_Year'
    ];

    public function faculty()
    {
        return $this->belongsToMany(Faculty::class, 'subject_faculty', 'subject_id', 'faculty_id');
    }

    public function sections()
    {
        return $this->belongsToMany(Sections::class, 'section_subject', 'subject_id', 'section_id');
    }
    public function schedules()
    {
        return $this->hasMany(Schedules::class, 'subject_id');
    }
}
