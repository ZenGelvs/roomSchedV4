@extends('layouts.app')

@section('title', 'Faculty Schedules')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h2 class="text-center mb-4">Schedules for {{ $faculty->name }}</h2>
        </div>
    </div>
            <!-- Day-wise Schedule Table -->
            <div class="card mt-4">
                <div class="card-body">
                    <div class="row">
                        @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
                            <div class="col-md-2">
                                <h5 class="text-center">{{ $day }}</h5>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Schedule</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $startTime = "07:00";
                                            $colorMap = [];
                                        @endphp
                                        @foreach ($faculty->subjects as $subject)
                                            @php
                                                $subjectSchedules = $subject->schedules ?? collect();
                                            @endphp
                                            @foreach ($subjectSchedules->where('day', $day)->sortBy('start_time') as $schedule)
                                                @php
                                                    $subjectKey = $subject->Description . $subject->Subject_Code;
                                                    $color = isset($colorMap[$subjectKey]) ? $colorMap[$subjectKey] : '#' . substr(md5($subjectKey), 0, 6);
                                                    $colorMap[$subjectKey] = $color;
                                                    $textColor = (hexdec(substr($color, 1, 2)) * 0.299 + hexdec(substr($color, 3, 2)) * 0.587 + hexdec(substr($color, 5, 2)) * 0.114) > 186 ? '#000000' : '#FFFFFF';
                                                @endphp
                                                @if ($schedule->start_time > $startTime)
                                                    <tr>
                                                        <td style="background-color: #f2f2f2;">
                                                            <p><strong>Time:</strong> {{ $startTime }} - {{ $schedule->start_time }}</p>
                                                            <p><em>No schedule</em></p>
                                                        </td>
                                                    </tr>
                                                @endif
                                                <tr>
                                                    <td style="background-color: {{ $color }}; color: {{ $textColor }};">
                                                        <p><strong>Time:</strong> {{ $schedule->start_time }} - {{ $schedule->end_time }}</p>
                                                        <p><strong>Subject Code:</strong> {{ $subject->Subject_Code }}</p>
                                                        <p><strong>Subject:</strong> {{ $subject->Description }}</p>
                                                        <p><strong>Type:</strong> {{ $schedule->type }}</p>
                                                        <p><strong>Room:</strong> {{ $schedule->room->room_id }} {{ $schedule->room->room_name }}</p>
                                                        <p><strong>Section:</strong> {{ $schedule->section->program_name }} {{ $schedule->section->section }}</p> 
                                                    </td>
                                                </tr>
                                                @php
                                                    $startTime = $schedule->end_time;
                                                @endphp
                                            @endforeach
                                        @endforeach
                                        @if ($startTime < "19:00")
                                            <tr>
                                                <td style="background-color: #f2f2f2; ">
                                                    <p><strong>Time:</strong> {{ $startTime }} - 19:00</p>
                                                    <p><em>No schedule</em></p>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
    </div>
</div>
@endsection