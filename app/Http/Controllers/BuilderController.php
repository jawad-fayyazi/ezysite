<?php

namespace App\Http\Controllers;

use Filament\Notifications\Notification;
use App\Models\Project;
use Illuminate\Http\Request;


class BuilderController extends Controller
{
    public function show($project_id, $project_name_from_url)
    {
        // Fetch the project from the database using the project_id
        $project = Project::find($project_id);

        // If the project doesn't exist, return 404
        if (!$project || $project->user_id != auth()->id()) {
            abort(404); // Prevent unauthorized access to other user's projects
        }

        // Check if the project name in the URL matches the project name from the database
        if (strtolower($project->project_name) !== strtolower($project_name_from_url)) {
            abort(404);
        }

        // Pass the project to the view
        return view('builder.index', [
            'project' => $project,
            'project_name' => $project->project_name,
            'project_id' => $project->project_id,
        ]);
    }


    // Load project data
    public function loadProjectData($project_id)
    {
        // Find the project data using the project_id
        $projectData = Project::where('project_id', $project_id)->first();

        if ($projectData) {
            // Return the raw project JSON (similar to raw PHP)
            return response($projectData->project_json, 200)
                ->header('Content-Type', 'application/json');
        } else {
            // Return an error if no project found
            return response()->json([
                'status' => 'error',
                'message' => 'No canvas data found for the specified project_id'
            ], 404);
        }
    }

    // Save project data
    public function saveProjectData(Request $request, $project_id)
    {
        // Get raw POST data from the request (similar to file_get_contents in PHP)
        $projects_data = $request->getContent();

        // Make sure project_id and project_json are provided
        if (!$project_id || empty($projects_data)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Project ID or data not provided.'
            ], 400); // Bad request
        }

        // Validate the JSON data (you could add custom validation if needed)
        $project_json = json_decode($projects_data, true); // Decode to check if it's valid JSON

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid JSON data received.'
            ], 400); // Bad request
        }

        // Check if the project exists in the database
        $projectData = Project::where('project_id', $project_id)->first();

        if (!$projectData) {
            return response()->json([
                'status' => 'error',
                'message' => 'Project not found.'
            ], 404); // Not found
        }

        // Update the existing project's data
        $projectData->project_json = $projects_data;
        $projectData->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Project data updated successfully',
            'data' => $projectData->project_json
        ]);
    }

    public function showProjectDetails($project_id)
    {
        $project = Project::find($project_id);

        // If the project doesn't exist, return 404
        if (!$project || $project->user_id != auth()->id()) {
            return redirect()->route('dashboard')->with('error', 'Project not found.');
        }

        // Pass the project to the view
        return view('builder.details', compact('project'));
    }


    public function deleteProject($project_id)
    {
        // Find the project by its ID
        $project = Project::where('project_id', $project_id)->first();

        if (!$project) {
            return redirect()->route('dashboard')->with('error', 'Project not found.');
        }

        // Ensure the project belongs to the current user
        if ($project->user_id !== auth()->id()) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized action.');
        }

        // Delete the project
        $project->delete();

        return redirect()->route('dashboard')->with('success', 'Project deleted successfully.');
    }


    public function create()
    {
        return view('builder.create');
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'project_name' => 'required|string|max:255',
        ]);


        // Check if a project with the same name (case insensitive) already exists
        $existingProject = Project::whereRaw('LOWER(project_name) = ?', [strtolower($request->input('project_name'))])->first();

        if ($existingProject) {
            // Return error if project name already exists
            return back()->with('error', 'A project with this name already exists.');
        }

        

        // Create a new project
        $project = Project::create([
            'project_name' => $request->input('project_name'),
            'user_id' => auth()->id(), // Associate with the logged-in user
            'project_json' => json_encode([
                'assets' => [],
                'styles' => [],
                'pages' => [
                    ['name' => 'Home']
                ],
                'symbols' => [],
                'dataSources' => []
            ])
        ]);
        
        // Redirect to the project details page
        return redirect()->route('project.details', ['project_id' => $project->project_id])
            ->with('success', 'Project created successfully!');
    }


    public function allShow()
    {
        $projects = Project::where('user_id', auth()->id())->get();

        return view('builder.projects', compact('projects'));
    }


    public function rename(Request $request, $project_id)
    {
        // Validate the new project name
        $request->validate([
            'project_name' => 'required|string|max:255',
        ]);

        // Find the project by ID
        $project = Project::findOrFail($project_id);

        // Check if another project with the same name exists
        $existingProject = Project::where('project_name', $request->project_name)->first();
        if ($existingProject) {
            return back()->with('error', 'A project with this name already exists.');
        }

        // Update the project name
        $project->project_name = $request->project_name;
        $project->save();

        return back()->with('success', 'Project renamed successfully!');
    }

    public function duplicate($project_id)
    {
        // Find the project by ID
        $project = Project::findOrFail($project_id);

        // Create a duplicate project with the same project data
        $newProject = $project->replicate(); // Replicates the model's data
        $newProject->project_name = $project->project_name . ' (Copy)'; // Add a suffix to the project name
        $newProject->user_id = auth()->id(); // Set the current user's ID
        $newProject->save(); // Save the new project

        return back()->with('success', 'Project duplicated successfully!');
    }



}