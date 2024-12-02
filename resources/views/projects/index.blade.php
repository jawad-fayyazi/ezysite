@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Your Projects</h1>


    <div class="project-list mt-4">
        @if($projects->isEmpty())
        <p>No projects found. Create your first project!</p>
        @else
        <ul>
            @foreach($projects as $project)
            <li>
                <a href="{{ route('projects.builder',"?project_id=", $project->id) }}">{{ $project->project_name }}</a>

            </li>
            @endforeach
        </ul>
        @endif
    </div>
</div>
@endsection