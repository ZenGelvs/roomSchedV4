@extends('layouts.app')

@section('title', 'Manage Sections')

@section('content')
    <div class="container mt-4">
        <h2 class="text-center mb-4">Manage Sections</h2>
        @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        @endif
        
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Add Sections</h5>
            </div>
            <div class="card-body">
                <!-- Button to toggle form -->
                <button class="btn btn-danger mb-3" type="button" data-toggle="collapse" data-target="#addSectionForm" aria-expanded="false" aria-controls="addSectionForm">
                    Add Section
                </button>

                <!-- Collapsible form for adding section -->
                <div class="collapse" id="addSectionForm">
                    <div class="card card-body">
                        <form action="{{ route('department.store') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="program_name">Program Name:</label>
                                <select class="form-control" id="program_name" name="program_name" required>
                                    <option value="">Select Program</option>
                                    @foreach($programs as $program)
                                        <option value="{{ $program->program_name }}">{{ $program->program_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="year_level">Year Level:</label>
                                <select class="form-control" id="year_level" name="year_level" required>
                                    <option value="">Select A Year Level</option>
                                    @foreach($programs as $program)
                                        @for($i = 1; $i <= $program->years; $i++)
                                            <option value="{{ $i }}">{{ $i == 1 ? $i . 'st' : ($i == 2 ? $i . 'nd' : ($i == 3 ? $i . 'rd' : $i . 'th')) }}</option>
                                        @endfor
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="section">Section:</label>
                                <label id="year_level_label" class="mb-2"></label>
                                <input type="number" class="form-control" id="section" name="section" placeholder="Enter section suffix" min="1" max="9" required>
                            </div>
                            <button type="submit" class="btn btn-success">Add Section</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('scripts')
    <script>

        function updateYearLevelLabel() {
                var yearLevel = $('#year_level').val(); 
                var section = $('#section').val();
                
                if (section !== '') {
                    $('#year_level_label').text(yearLevel + "0" + section);
                } else {
                    $('#year_level_label').text(yearLevel + "0");
                }
            }

        
            $('#program_name').change(function() {
                var programName = $(this).val();
                var program = {!! $programs->toJson() !!}.find(program => program.program_name === programName);
                var years = program ? program.years : 0;
                $('#year_level').empty();
                for (var i = 1; i <= years; i++) {
                    $('#year_level').append(`<option value="${i}">${i == 1 ? i + 'st' : (i == 2 ? i + 'nd' : (i == 3 ? i + 'rd' : i + 'th'))}</option>`);
                }
                updateYearLevelLabel();
            });

            $('#year_level').change(function() {
                updateYearLevelLabel();
            });

            $('#section').on('input', function() {
                updateYearLevelLabel(); 
            });

            $(document).ready(function() {

                $('#program_name').change();
                updateYearLevelLabel(); 
            });
    </script>
    @endsection

@endsection
