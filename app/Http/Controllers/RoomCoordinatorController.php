<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\Sections;
use App\Models\Schedules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoomCoordinatorController extends Controller
{
    public function deleteRoom($id)
    {
        $room = Room::findOrFail($id);
        $room->delete();

        return redirect()->route('dashboard.roomCoordIndex')->with('success', 'Room deleted successfully.');
    }

    public function addRoom(Request $request)
    {
        $request->validate([
            'roomName' => 'required|string|max:255',
            'roomID' => 'required|string|max:255',
            'roomType' => 'required|string|max:255',
            'building' => 'required|string|max:255',
        ]);

        $existingRoom = Room::where('room_name', $request->roomName)
            ->where('room_id', $request->roomID)
            ->where('room_type', $request->roomType)
            ->where('building', $request->building)
            ->exists();

        if ($existingRoom) {
            return redirect()->back()->with('error', 'Room with the same details already exists.');
        }

        $duplicateRoom = Room::where('room_name', $request->roomName)
            ->where('room_id', $request->roomID)
            ->exists();

        if ($duplicateRoom) {
            return redirect()->back()->with('error', 'Room with the same name and ID already exists.');
        }

        $roomWithSameID = Room::where('room_id', $request->roomID)->exists();

        if ($roomWithSameID) {
            return redirect()->back()->with('error', 'Room with the same ID already exists.');
        }

        Room::create([
            'room_name' => $request->roomName,
            'room_id' => $request->roomID,
            'room_type' => $request->roomType,
            'building' => $request->building,
        ]);

        return redirect()->back()->with('success', 'Room added successfully.');
    }

    public function editRoom($id)
    {
        $room = Room::findOrFail($id);
        return view('roomCoordinator.edit_room', compact('room'));
    }

    public function updateRoom(Request $request, $id)
    {
        $request->validate([
            'roomName' => 'required|string|max:255',
            'roomID' => 'required|string|max:255',
            'roomType' => 'required|string|max:255',
            'building' => 'required|string|max:255',
        ]);

        $room = Room::findOrFail($id);

        $existingRoom = Room::where('room_name', $request->roomName)
                            ->where('room_id', $request->roomID)
                            ->where('room_type', $request->roomType)
                            ->where('building', $request->building)
                            ->where('id', '!=', $id)
                            ->exists();

        if ($existingRoom) {
            return redirect()->back()->with('error', 'Room with the same details already exists.');
        }

        $duplicateRoom = Room::where('room_name', $request->roomName)
            ->where('room_id', $request->roomID)
            ->exists();

        if ($duplicateRoom) {
            return redirect()->back()->with('error', 'Room with the same name and ID already exists.');
        }

        $roomWithSameID = Room::where('room_id', $request->roomID)->exists();

        if ($roomWithSameID) {
            return redirect()->back()->with('error', 'Room with the same ID already exists.');
        }

        $room->update([
            'room_name' => $request->roomName,
            'room_id' => $request->roomID,
            'room_type' => $request->roomType,
            'building' => $request->building,
        ]);

        return redirect()->route('dashboard.roomCoordIndex')->with('success', 'Room updated successfully.');
    }

    public function roomSchedule($roomId)
    {
        $room = Room::findOrFail($roomId);
        $schedules = Schedules::where('room_id', $roomId)->with('subject')->with('section')->get();
        return view('roomCoordinator.room_sched', compact('room', 'schedules'));
    }

    public function facultySchedIndex(Request $request)
    {
        $query = Faculty::with('subjects');

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $facultyList = $query->paginate(5);

        return view('roomCoordinator.faculty_index', compact('facultyList'));
    }

    public function viewFacultySchedule(Request $request, $facultyId)
    {
        $facultyId = $request->input('faculty', $facultyId);
        $faculty = Faculty::findOrFail($facultyId); 

        $schedules = collect();
        foreach ($faculty->subjects as $subject) {
            foreach ($subject->schedules as $schedule) {
                $section = $subject->sections()->first();
                if ($section) {
                    $schedule->section = $section;
                }
                $schedules->push($schedule);
            }
        }

        return view('roomCoordinator.faculty_sched', compact('faculty', 'schedules'));
    }

    public function sectionScheduleIndex(Request $request)
    {
        $search = $request->input('search');
        
        $sections = Sections::query()
                        ->where('program_name', 'like', "%$search%")
                        ->orWhere('year_level', 'like', "%$search%")
                        ->orWhere('section', 'like', "%$search%")
                        ->orWhere('college', 'like', "%$search%")
                        ->orWhere('department', 'like', "%$search%")
                        ->paginate(5)
                        ->appends(['search' => $search]);

        return view('roomCoordinator.sections_index', compact('sections'));
    }

    public function viewSectionSchedule($sectionId)
    {
        $section = Sections::findOrFail($sectionId);
        $section = Sections::findOrFail($sectionId);
        $schedules = $section->schedules()->with('subject', 'room')->get();
        $subjects = $section->subjects()->with(['schedules' => function($query) use ($sectionId) {
            $query->where('section_id', $sectionId);
        }, 'faculty'])->get();

        return view('roomCoordinator.sections_schedule', compact('section', 'schedules', 'subjects'));

    }

    public function destroySchedule(Schedules $schedule)
    {
        $schedule->delete();
        return redirect()->back()->with('success', 'Schedule deleted successfully');
    }

    public function editSchedule(Schedules $schedule)
    {   
        $schedule->load('section');
        $sections = Sections::with('subjects')->get();
        $rooms = Room::all(); 
        return view('roomCoordinator.edit_schedule', compact('schedule', 'rooms', 'sections'));
    }

    public function updateSchedule(Request $request, Schedule $schedule)
    {
        $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'type' => 'required|string',
            'room_id' => 'required|string',
            'department' => 'required|string',
            'college' => 'required|string',
        ]);

        $existingSchedule = Schedules::where('day', $request->day)
        ->where('start_time', $request->startTime)
        ->where('end_time', $request->endTime)
        ->where('section_id', $request->sectionId)
        ->where('subject_id', $request->subjectId)
        ->where('type', $request->type)
        ->where('room_id', $request->roomId)
        ->exists();

        if ($existingSchedule) {
            return redirect()->back()->with('error', 'A schedule with the same details already exists.');
        }

        $overlappingSchedule = Schedules::where('day', $request->day)
        ->where('section_id', $request->sectionId)
        ->where('room_id', $request->roomId)
        ->where(function ($query) use ($request) {
            $query->whereRaw('? between start_time and end_time', [$request->startTime])
                ->orWhereRaw('? between start_time and end_time', [$request->endTime])
                ->orWhere(function ($query) use ($request) {
                    $query->where('start_time', '>=', $request->startTime)
                        ->where('end_time', '<=', $request->endTime);
                });
        })
        ->exists();
    
        if ($overlappingSchedule) {
            return redirect()->back()->with('error', 'There is an overlapping schedule for the selected room and time slot.');
        }

        $overlappingRoomSchedule = Schedules::where('day',  $request->day)
                    ->where('start_time', '<',  $request->endTime)
                    ->where('end_time', '>', $request->startTime)
                    ->where('room_id', $request->roomId)
                    ->exists();

        if ($overlappingRoomSchedule) {
            return redirect()->back()->with('error', 'There is an overlapping schedule for the selected room and time slot.');
        }
        
        $subject = Subject::find($request->subjectId);
        $facultyIds = $subject->faculty->pluck('id');

        $overlappingFacultySchedule = Schedules::where('day', $request->day)
            ->whereIn('subject_id', function ($query) use ($facultyIds) {
                $query->select('subject_id')
                    ->from('subject_faculty')
                    ->whereIn('faculty_id', $facultyIds);
            })
            ->where(function ($query) use ($request) {
                $query->where('start_time', '<', $request->endTime)
                    ->where('end_time', '>', $request->startTime);
            })
            ->exists();

        if ($overlappingFacultySchedule) {
            return redirect()->back()->with('error', 'There is an overlapping schedule for the faculty assigned to this subject.');
        }

        $schedule->update($request->all());

        return redirect()->back()->with('success', 'Schedule updated successfully');
    }

    public function addSchedule()
    {   
        $rooms = Room::all();

        $sections = Sections::all();
        
        $faculties = Faculty::all(); 

        return view('roomCoordinator.add_sched',  compact('rooms', 'sections', 'faculties'));
    }

    public function storeSchedule(Request $request)
    {
        $request->validate([
            'day' => 'required|string',
            'startTime' => 'required',
            'endTime' => 'required',
            'sectionId' => 'required',
            'subjectId' => 'required',
            'type' => 'required',
            'roomId' => 'required',
        ]);

        $section = Sections::findOrFail($request->sectionId);

        $existingSchedule = Schedules::where('day', $request->day)
            ->where('start_time', $request->startTime)
            ->where('end_time', $request->endTime)
            ->where('section_id', $request->sectionId)
            ->where('subject_id', $request->subjectId)
            ->where('type', $request->type)
            ->where('room_id', $request->roomId)
            ->exists();

        if ($existingSchedule) {
            return redirect()->back()->with('error', 'A schedule with the same details already exists.');
        }

        $overlappingSchedule = Schedules::where('day', $request->day)
        ->where('section_id', $request->sectionId)
        ->where(function ($query) use ($request) {
            $query->where('start_time', '<', $request->endTime)
                ->where('end_time', '>', $request->startTime);
        })
        ->exists();

        if ($overlappingSchedule) {
            return redirect()->back()->with('error', 'There is an overlapping schedule for this section.');
        }

        $overlappingRoomSchedule = Schedules::where('day', $request->day)
            ->where('room_id', $request->roomId)
            ->where(function ($query) use ($request) {
                $query->where('start_time', '<', $request->endTime)
                    ->where('end_time', '>', $request->startTime);
            })
            ->exists();

        if ($overlappingRoomSchedule) {
            return redirect()->back()->with('error', 'There is an overlapping schedule for the selected room and time slot.');
        }

        $subject = Subject::find($request->subjectId);
        $facultyIds = $subject->faculty->pluck('id');

        $overlappingFacultySchedule = Schedules::where('day', $request->day)
            ->whereIn('subject_id', function ($query) use ($facultyIds) {
                $query->select('subject_id')
                    ->from('subject_faculty')
                    ->whereIn('faculty_id', $facultyIds);
            })
            ->where(function ($query) use ($request) {
                $query->where('start_time', '<', $request->endTime)
                    ->where('end_time', '>', $request->startTime);
            })
            ->exists();

        if ($overlappingFacultySchedule) {
            return redirect()->back()->with('error', 'There is an overlapping schedule for the faculty assigned to this subject.');
        }

        Schedules::create([
            'day' => $request->day,
            'start_time' => $request->startTime,
            'end_time' => $request->endTime,
            'section_id' => $request->sectionId,
            'subject_id' => $request->subjectId,
            'type' => $request->type,
            'room_id' => $request->roomId,
            'college' =>  $section->college,
            'department' => $section->department,
        ]);

        return redirect()->back()->with('success', 'Schedule created successfully.');
    }

    public function automaticSchedule(Request $request)
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id',
        ]);

        $preferredStartTime = $request->preferred_start_time;
        $preferredEndTime = $request->preferred_end_time;
        $preferredBuilding = $request->preferred_building;

        // Retrieve the selected subject
        $subject = Subject::findOrFail($request->subjectId);

        // Retrieve rooms and filter by preferred room and building
        $roomsQuery = Room::where('room_type', 'Lecture');

        if ($request->preferredRoom !== 'Any') {
            $roomsQuery->where('id', $request->preferredRoom);
        }

        if ($preferredBuilding !== 'Any') {
            $roomsQuery->where('building', $preferredBuilding);
        }

        $rooms = $roomsQuery->get();

        // Determine preferred days to iterate through
        $daysOfWeek = ($request->preferred_day !== 'Any') ? [$request->preferred_day] : ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        // Track scheduled time slots per day
        $scheduledSlots = [];

        $schedulingSuccess = false;
        $errorReasons = [];

        // Remove the check for existing schedules
        foreach ($daysOfWeek as $day) {
            // Check if the current day has available time slots
            if (!isset($scheduledSlots[$day])) {
                $scheduledSlots[$day] = [];
            }

            // Find an available time slot for the subject on the current day
            $result = $this->findAvailableSlot($rooms, $day, $preferredStartTime, $preferredEndTime, $scheduledSlots[$day], $request->section_id, $subject->id);
            $availableSlot = $result['slot'];
            $reason = $result['reason'];

            if ($availableSlot) {
                // Create a new schedule for the subject
                Schedules::create([
                    'day' => $day,
                    'start_time' => $availableSlot['start_time'],
                    'end_time' => $availableSlot['end_time'],
                    'section_id' => $request->section_id,
                    'subject_id' => $subject->id,
                    'type' => 'Lecture',
                    'room_id' => $availableSlot['room_id'],
                    'college' =>  $section->college,
                    'department' => $section->department,
                ]);

                // Mark the time slot as scheduled
                $scheduledSlots[$day][] = [
                    'start_time' => $availableSlot['start_time'],
                    'end_time' => $availableSlot['end_time'],
                ];

                $schedulingSuccess = true;
                break; // Break the loop once scheduled for one day
            } else {
                // Collect the reason for failure
                $errorReasons[$day][] = $reason;
            }
        }

        if ($schedulingSuccess) {
            $message = 'Automatic scheduling completed successfully.';
            return redirect()->back()->with('success', $message);
        } else {
            // Prepare detailed error messages
            $detailedErrors = [];
            foreach ($errorReasons as $day => $reasons) {
                $reasonsCount = array_count_values($reasons);
                foreach ($reasonsCount as $reason => $count) {
                    if ($reason !== null) {
                        $detailedErrors[] = " Day $day: $reason ";
                    }
                }
            }
            $message = 'Automatic scheduling failed. No available time slots found. ' . implode(', ', $detailedErrors);
            return redirect()->back()->with('error', $message);
        }
    }

    private function findAvailableSlot($rooms, $day, $preferredStartTime, $preferredEndTime, $scheduledSlots, $sectionId, $subjectId)
    {
        // Sort scheduled slots by start time
        usort($scheduledSlots, function ($a, $b) {
            return strtotime($a['start_time']) - strtotime($b['start_time']);
        });

        // Retrieve the faculty members for the subject
        $subject = Subject::find($subjectId);
        $facultyIds = $subject->faculty->pluck('id');

        // Iterate through rooms
        foreach ($rooms as $room) {
            if ($room->room_type === 'Lecture') {
                // Initialize start time based on the end time of the last scheduled slot
                $startTime = empty($scheduledSlots) ? $preferredStartTime : end($scheduledSlots)['end_time'];
                $endTime = $preferredEndTime;

                // Check if the calculated time slot overlaps with any existing schedules for the same section, day, and time
                $overlappingSchedule = Schedules::where('day', $day)
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime)
                    ->where('section_id', $sectionId)
                    ->exists();

                // Check if the calculated time slot overlaps with any existing schedules for the same room and day
                $overlappingRoomSchedule = Schedules::where('day', $day)
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime)
                    ->where('room_id', $room->id)
                    ->exists();

                // Check if the calculated time slot overlaps with any existing schedules for the faculty and day
                $overlappingFacultySchedule = Schedules::where('day', $day)
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime)
                    ->whereIn('subject_id', function ($query) use ($facultyIds) {
                        $query->select('subject_id')
                            ->from('subject_faculty')
                            ->whereIn('faculty_id', $facultyIds);
                    })
                    ->exists();

                if (!$overlappingSchedule && !$overlappingRoomSchedule && !$overlappingFacultySchedule) {
                    return [
                        'slot' => [
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'room_id' => $room->id,
                        ],
                        'reason' => null,
                    ];
                }

                if ($overlappingSchedule) {
                    return [
                        'slot' => null,
                        'reason' => 'Overlapping schedule for this Section',
                    ];
                }

                if ($overlappingFacultySchedule) {
                    return [
                        'slot' => null,
                        'reason' => 'Overlapping faculty schedule',
                    ];
                }
            }
        }

        return [
            'slot' => null,
            'reason' => 'No available slots',
        ];
    }

    public function assignRoom(Request $request)
    {

        $room_ids = $request->input('room_ids');
        $user_id = $request->input('user_id');

        if (empty($request->room_ids)) {
            return redirect()->back()->with('error', 'Please select a room to assign.');
        }

        $user = User::findOrFail($request->user_id);
    
        $assignedRoomIds = $user->rooms()->pluck('room.id')->toArray();

        $newRoomIds = array_diff($request->room_ids, $assignedRoomIds);

        if (!empty($newRoomIds)) {
            $user->rooms()->syncWithoutDetaching($newRoomIds);

            return redirect()->back()->with('success', 'Room is assigned to user successfully.');
        } else {
            return redirect()->back()->with('error', 'Room/s are already assigned to the user.');
        }
    }

    public function showAssignRoomsToFaculty(Request $request)
    {   
        $search = $request->input('search');

        $users = User::whereNotIn('college', ['ADMIN'])
                    ->whereNotIn('department', ['ROOM COORDINATOR'])
                    ->get();

        $assignedRoomIds = []; 

        foreach ($users as $user) {
            foreach ($user->rooms as $room) {
                $assignedRoomIds[] = $room->id;
            }
        }

        $rooms = Room::query();

        if ($search) {
            $rooms->where(function ($query) use ($search) {
                $query->where('room_id', 'like', '%' . $search . '%')
                    ->orWhere('room_name', 'like', '%' . $search . '%')
                    ->orWhere('building', 'like', '%' . $search . '%')
                    ->orWhere('room_type', 'like', '%' . $search . '%');
            });
        }

        $rooms = $rooms->paginate(10)->appends(['search' => $search]); 

        return view('roomCoordinator.assign_rooms', compact('users', 'rooms'));
    }    

    public function unassignRoom(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:room,id',
        ]);

        $user = User::findOrFail($request->user_id);
        $user->rooms()->detach($request->room_id);

        return redirect()->back()->with('success', 'Room unassigned successfully.');
    }

}   
