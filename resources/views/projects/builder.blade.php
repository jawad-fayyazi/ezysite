@extends('layouts.app')

@section('content')
<!-- GrapesJS CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/grapesjs/0.17.27/css/grapes.min.css" rel="stylesheet" />

<div class="container">
    <h1>Edit Project: {{ $project->project_name }}</h1>

    <!-- GrapesJS Editor Container -->
    <div id="gjs" style="height: 500px;"></div>

    <button id="saveProject">Save Project</button>
</div>

<script>
    // Initialize GrapesJS editor
    const editor = grapesjs.init({
        container: '#gjs',
        fromElement: true,
        height: '100%',
        storageManager: { type: 'remote' },
        components: {!! json_encode($project->project_json) !!}
    });

    // Save project data
    document.getElementById('saveProject').addEventListener('click', async () => {
        const projectJson = editor.getComponents();
        const projectName = "{{ $project->project_name }}";

        const response = await fetch('{{ route('projects.update', $project->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                project_name: projectName,
                project_json: JSON.stringify(projectJson),
            }),
        });

        const result = await response.json();
        alert(result.message);
    });
</script>
<!-- GrapesJS JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/grapesjs/0.17.27/grapes.min.js"></script>

@endsection