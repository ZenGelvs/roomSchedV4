<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\Subject;
use App\Models\Sections;
use App\Models\Programs; 
use App\Models\Schedules;
use Illuminate\Http\Request;
use App\Imports\SubjectsImport;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;


class SubjectController extends Controller
{
    public function index()
    {
        $programs = Programs::all();
        return view('admin.manage_subjects', compact('programs'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'excelFile' => 'required|mimes:xlsx,xls',
        ]);

        $file = $request->file('excelFile');

        $import = new SubjectsImport;
        $importedData = Excel::toCollection($import, $file)->first();

        if (!$importedData) {
            return redirect()->back()->with('error', 'Failed to import data from the Excel file.');
        }

        $validationErrors = $this->validateImportedData($importedData);

        if ($validationErrors->count() > 0) {
            return redirect()->back()->withErrors(['excelFile' => $validationErrors])->withInput();
        }

        $duplicates = [];
        foreach ($importedData as $data) {

            $existingSubject = Subject::where('Subject_Code', $data['subject_code'])
                ->where('Description', $data['description'])
                ->where('Lec', $data['lec'])
                ->where('Lab', $data['lab'])
                ->where('Units', $data['units'])
                ->where('Pre_Req', $data['pre_req'])
                ->where('Year_Level', $data['year_level'])
                ->where('Semester', $data['semester'])
                ->where('College', $data['college'])
                ->where('Department', $data['department'])
                ->where('Program', $data['program'])
                ->where('Academic_Year', $data['curriculum'])
                ->first();

            if ($existingSubject) {
                $duplicates[] = $data;
            }
        }

        if (!empty($duplicates)) {
            $message = 'The following subjects already exist:';
            foreach ($duplicates as $duplicate) {
                $message .= "\n" . $duplicate['subject_code'] . ' - ' . $duplicate['description'];
            }
            return redirect()->back()->with('error', $message);
        }

        foreach ($importedData as $data) {
            Subject::create([
                'Subject_Code' => $data['subject_code'],
                'Description' => $data['description'],
                'Lec' => $data['lec'],
                'Lab' => $data['lab'],
                'Units' => $data['units'],
                'Pre_Req' => $data['pre_req'],
                'Year_Level' => $data['year_level'],
                'Semester' => $data['semester'],
                'College' => $data['college'],
                'Department' => $data['department'],
                'Program' => $data['program'],
                'Academic_Year' => $data['curriculum'],
            ]);
        }

        return redirect()->back()->with('success', 'Subjects imported successfully!');
    }

    private function validateImportedData($importedData)
    {
        $errors = collect();

        foreach ($importedData as $index => $data) {
            if (!isset($data['subject_code']) || empty($data['subject_code'])) {
                $errors->push("Row {$index}: Subject code is required.");
            }

            if (!isset($data['description']) || empty($data['description'])) {
                $errors->push("Row {$index}: Description is required.");
            }

            if (!isset($data['lec']) || !is_numeric($data['lec']) || $data['lec'] < 0) {
                $errors->push("Row {$index}: Lec must be a non-negative numeric value.");
            }

            if (!isset($data['lab']) || !is_numeric($data['lab']) || $data['lab'] < 0) {
                $errors->push("Row {$index}: Lab must be a non-negative numeric value.");
            }

            if (!isset($data['units']) || !is_numeric($data['units']) || $data['units'] <= 0) {
                $errors->push("Row {$index}: Units must be a positive numeric value.");
            }

            if (!isset($data['pre_req']) || !is_string($data['pre_req'])) {
                $errors->push("Row {$index}: Pre-Requisite must be a string.");
            }

            if (!isset($data['year_level']) || empty($data['year_level'])) {
                $errors->push("Row {$index}: Year Level is required.");
            }

            if (!isset($data['semester']) || empty($data['semester'])) {
                $errors->push("Row {$index}: Semester is required.");
            }

            if (!isset($data['college']) || empty($data['college'])) {
                $errors->push("Row {$index}: College is required.");
            }

            if (!isset($data['department']) || empty($data['department'])) {
                $errors->push("Row {$index}: Department is required.");
            }

            if (!isset($data['program']) || empty($data['program'])) {
                $errors->push("Row {$index}: Program is required.");
            }

            if (!isset($data['curriculum']) || empty($data['curriculum'])) {
                $errors->push("Row {$index}: Academic Year is required.");
            }
        }

        return $errors;
    }

    public function store(Request $request)
    {
        $request->validate([
            'Subject_Code' => 'required',
            'Description' => 'required',
            'Lec' => 'required|numeric|min:0',
            'Lab' => 'required|numeric|min:0',
            'Pre_Req' => 'required',
            'Year_Level' => 'required|in:1st,2nd,3rd,4th', 
            'Semester' => 'required|in:1,2,Summer', 
            'College' => 'required|in:COECSA,CAMS,CAS,CBA,CFAD,CITHM,NURSING', 
            'Department' => 'required',
            'Program' => 'required',
            'Academic_Year' => 'required',
        ]);

        $totalUnits = $request->input('Lec') + $request->input('Lab');

        $existingSubject = Subject::where('Subject_Code', $request->input('Subject_Code'))
                                    ->where('Description', $request->input('Description'))
                                    ->where('Pre_Req', $request->input('Pre_Req'))
                                    ->where('Year_Level', $request->input('Year_Level'))
                                    ->where('Semester', $request->input('Semester'))
                                    ->where('College', $request->input('College'))
                                    ->where('Department', $request->input('Department'))
                                    ->where('Program', $request->input('Program'))
                                    ->where('Academic_Year', $request->input('Academic_Year'))
                                    ->first();

        if ($existingSubject) {
        $errorMessage = 'A subject with the same data already exists.';
        return redirect()->route('dashboard.adminIndex')->withErrors(['error' => $errorMessage])->with('subjectError', $errorMessage);
        }

        Subject::create([
            'Subject_Code' => $request->input('Subject_Code'),
            'Description' => $request->input('Description'),
            'Lec' => $request->input('Lec'),
            'Lab' => $request->input('Lab'),
            'Pre_Req' => $request->input('Pre_Req'),
            'Year_Level' => $request->input('Year_Level'),
            'Semester' => $request->input('Semester'),
            'College' => $request->input('College'),
            'Department' => $request->input('Department'),
            'Program' => $request->input('Program'),
            'Academic_Year' => $request->input('Academic_Year'),
            'Units' => $totalUnits, 
        ]);

        return redirect()->route('dashboard.adminIndex')->with('success', 'Subject added successfully!');
    }

    public function deleteAll()
    {
        $subjects = Subject::all();

        foreach ($subjects as $subject) {
            $subject->delete();
        }

        return redirect()->back()->with('success', 'All subjects and related schedules have been deleted successfully.');
    }

    public function delete($id)
    {
        $subject = Subject::findOrFail($id);

        $subject->schedules()->delete();

        $subject->delete();

        return redirect()->back()->with('success', 'Subject and related schedules have been deleted successfully.');
    }

    public function edit($id)
    {
        $subject = Subject::findOrFail($id);
        $programs = Programs::all();
        $collegeDepartments = [
            'COECSA' => ['DCS', 'DOE', 'DOA'],
            'CAMS' => ['CAMS Dept', 'CAMS Dept1', 'CAMS Dept2'],
            'CAS' => ['CAS Dept', 'CAS Dept1', 'CAS Dept2'],
            'CBA' => ['CBA Dept', 'CBA Dept1', 'CBA Dept2'],
            'CFAD' => ['CFAD Dept', 'CFAD Dept1', 'CFAD Dept2'],
            'CITHM' => ['Tourism', 'CITHM Dept1', 'CITHM Dept2'],
            'NURSING' => ['NURSING Dept', 'NURSING Dept1', 'NURSING Dept2']
        ];
        return view('admin.edit_subject', compact('subject', 'programs', 'collegeDepartments'));
    }
    

    public function update(Request $request, $id)
    {
        $request->validate([
            'Subject_Code' => 'required',
            'Description' => 'required',
            'Lec' => 'required|numeric|min:0',
            'Lab' => 'required|numeric|min:0',
            'Pre_Req' => 'required',
            'Year_Level' => 'required|in:1st,2nd,3rd,4th', 
            'Semester' => 'required|in:1,2,Summer', 
            'College' => 'required|in:COECSA,CAMS,CAS,CBA,CFAD,CITHM,NURSING', 
            'Department' => 'required',
            'Program' => 'required',
            'Academic_Year' => 'required',
        ]);
        
        $subject = Subject::find($id);
    
        $existingSubject = Subject::where('Subject_Code', $request->input('Subject_Code'))
                                    ->where('Description', $request->input('Description'))
                                    ->where('Pre_Req', $request->input('Pre_Req'))
                                    ->where('Year_Level', $request->input('Year_Level'))
                                    ->where('Semester', $request->input('Semester'))
                                    ->where('College', $request->input('College'))
                                    ->where('Department', $request->input('Department'))
                                    ->where('Program', $request->input('Program'))
                                    ->where('Academic_Year', $request->input('Academic_Year'))
                                    ->where('id', '!=', $id) 
                                    ->first();
    
        if ($existingSubject) {
            $errorMessage = 'A subject with the same data already exists.';
            return redirect()->route('dashboard.adminIndex')->withErrors(['error' => $errorMessage])->with('subjectError', $errorMessage);
        }
        
        $subject->fill($request->except('Units'))->save();

        $lec = $request->input('Lec');
        $lab = $request->input('Lab');
        $totalUnits = $lec + $lab;
        $subject->update(['Units' => $totalUnits]);
    
        return redirect()->route('dashboard.adminIndex')->with('success', 'Subject updated successfully!');
    }
    
    public function departmentIndex(Request $request)
    {
        $userCollege = Auth::user()->college;
        $userDepartment = Auth::user()->department;

        $search = $request->input('search');
        $yearLevel = $request->input('Year_Level');
        $semester = $request->input('Semester');
        $college = $request->input('College');
        $department = $request->input('Department');
        $program = $request->input('Program');
        $academicYear = $request->input('Academic_Year');

        $subjectQuery = Subject::where('College', $userCollege)
                        ->where('Department', $userDepartment);

        if ($search) {
            $subjectQuery->where(function ($q) use ($search) {
                $q->where('Subject_Code', 'like', '%'.$search.'%')
                ->orWhere('Description', 'like', '%'.$search.'%')
                ->orWhere('Year_Level', 'like', '%'.$search.'%')
                ->orWhere('Program', 'like', '%'.$search.'%')
                ->orWhere('Academic_Year', 'like', '%'.$search.'%')
                ->orWhere('Semester', 'like', '%'.$search.'%')
                ->orWhereHas('faculty', function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%');
                });
            });
        }

        if ($yearLevel) {
            $subjectQuery->where('Year_Level', $yearLevel);
        }
        if ($semester) {
            $subjectQuery->where('Semester', $semester);
        }
        if ($college) {
            $subjectQuery->where('College', $college);
        }
        if ($department) {
            $subjectQuery->where('Department', $department);
        }
        if ($program) {
            $subjectQuery->where('Program', $program);
        }
        if ($academicYear) {
            $subjectQuery->where('Academic_Year', $academicYear);
        }

        $subjects = $subjectQuery->paginate(10)->appends($request->query());

        $faculty = Faculty::where('college', $userCollege)
                        ->where('department', $userDepartment)
                        ->get();

        $sections = Sections::where('college', $userCollege)
                            ->where('department', $userDepartment)
                            ->orderBy('section')
                            ->get()
                            ->groupBy('program_name'); 

        $yearLevels = Subject::distinct()->pluck('Year_Level');
        $semesters = Subject::distinct()->pluck('Semester');
        $colleges = Subject::distinct()->pluck('College');
        $departments = Subject::distinct()->pluck('Department');
        $programs = Subject::distinct()->pluck('Program');
        $academicYears = Subject::distinct()->pluck('Academic_Year');

        return view('department.subjects', compact('subjects', 'faculty', 'sections', 'yearLevels', 'semesters', 'colleges', 'departments', 'programs', 'academicYears'));
    }

    public function assignFaculty($subjectId, Request $request)
    {
        $subject = Subject::findOrFail($subjectId);
        $facultyId = $request->input('faculty_id');
        $facultyMember = Faculty::findOrFail($facultyId);
    
        $totalUnits = $facultyMember->subjects->sum('Units');
    
        $unitLimit = ($facultyMember->type === 'Part-Time') ? 12 : 24;
    
        $subjectUnits = $subject->Units;
    
       
    
        if (!$subject->faculty->contains($facultyId)) {
            $subject->faculty()->attach($facultyId);
            if (($totalUnits + $subjectUnits) > $unitLimit) {
                return redirect()->route('department.subjects')->with('error', 'Faculty assigned to subject successfully, but this subject will exceed the allowed unit limit for this faculty.');
            }
            else{
                return redirect()->route('department.subjects')->with('success', '.');
            }
        } else {
            return redirect()->route('department.subjects')->with('error', 'Faculty is already assigned to this subject.');
        }
    }
    
    public function removeFaculty($subjectId, $facultyId)
    {
        $subject = Subject::findOrFail($subjectId);
        $subject->faculty()->detach($facultyId);

        return redirect()->route('department.subjects')->with('success',  'Faculty removed from subject successfully.');
    }

    public function assignSubjects(Request $request)
    {
        $sectionId = $request->query('section_id');

        $section = Sections::find($sectionId);

        if (!$section) {
            return redirect()->back()->with('error', 'Section not found.');
        }

        $programName = $section->program_name;
        $yearLevel = $section->year_level;

        $subjectsForSection = Subject::where('Program', $programName)
            ->where('Year_Level', $yearLevel)
            ->get();

        $assignedSubjectIds = $section->subjects()->pluck('subjects.id')->toArray();

        $availableSubjects = $subjectsForSection->reject(function ($subject) use ($assignedSubjectIds) {
            return in_array($subject->id, $assignedSubjectIds);
        });

        $assignedSubjects = $section->subjects;
        $semesters = $subjectsForSection->pluck('Semester')->unique();
        $academicYears = $subjectsForSection->pluck('Academic_Year')->unique();

        return view('department.assign_subjects', compact('availableSubjects', 'assignedSubjects', 'programName', 'yearLevel', 'sectionId', 'section', 'semesters', 'academicYears'));
    }

    public function assignSectionToSubject(Request $request)
    {
        $sectionId = $request->input('section_id');
        $subjectIds = $request->input('subject');
    
        if ($subjectIds === null) {
            Log::info('No subject IDs provided.');
            return redirect()->back()->with('error', 'No subject IDs provided.');
        }
    
        $section = Sections::find($sectionId);
        if (!$section) {
            return redirect()->back()->with('error', 'Section not found.');
        }
    
        try {
            $subjectIdsArray = array_map('intval', is_array($subjectIds) ? $subjectIds : explode(',', $subjectIds));
    
            $section->subjects()->attach($subjectIdsArray);
            $attachedSubjects = $section->subjects()->pluck('subject_id')->toArray();
            return redirect()->back()->with('success', 'Subjects assigned to section successfully.');
        } catch (\Exception $e) {
            Log::error('Error attaching subjects to section: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to assign subjects to section.');
        }
    }

    public function unassignSubject(Subject $subject)
    {
        $section = $subject->sections()->first(); 
        $section->subjects()->detach($subject->id);

        return redirect()->back()->with('success', 'Subject unassigned successfully.');
    }

    public function view($id)
    {
        $subject = Subject::findOrFail($id);
        return view('admin.viewSubject', compact('subject'));
    }

}
