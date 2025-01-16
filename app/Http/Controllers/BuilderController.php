<?php

namespace App\Http\Controllers;

use Filament\Notifications\Notification;
use App\Models\Project;
use App\Models\WebPage;
use App\Models\HeaderFooter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use ZipArchive;


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




    public function deploy(Request $request, $project_id)
    {

        // Check if file is received
        if (!$request->hasFile('file')) {
            return response()->json([
                'status' => 'error',
                'message' => 'No file uploaded',
            ], 400);
        }
        
        // Validate the uploaded file
        $request->validate([
            'file' => 'required|file|mimes:zip',
        ]);
        
        // Fetch the project by ID and get the domain
        $project = Project::find($project_id);

        // If the project doesn't exist, return 404
        if (!$project || $project->user_id != auth()->id()) {
            return redirect()->route('dashboard')->with('error', 'Project not found.');
        }

        $domain = $project->domain;

        if (!$domain) {
            return response()->json([
                'status' => 'error',
                'message' => 'Domain not configured for this project',
            ], 400);
        }

        // Define the target directory
        $targetDir = "/var/www/domain/{$domain}";

        // Create the directory if it doesn't exist
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Handle the uploaded ZIP file
        $uploadedFile = $request->file('file');
        $zipPath = $uploadedFile->getPathname();

        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($targetDir); // Extract files to the target directory
            $zip->close();

            return response()->json([
                'status' => 'success',
                'domain' => $domain,
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to extract files',
            ], 500);
        }
    }


    public function pageSave(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'page_id' => 'required|string|max:255',
            'website_id' => 'required|integer|exists:projects_data,project_id',  // Ensure the website_id is valid
        ]);

        try {
            // Fetch the project from the database using the project_id
        $project = Project::find($request->website_id);

        // If the project doesn't exist, return 404
        if (!$project || $project->user_id != auth()->id()) {
            abort(404); // Prevent unauthorized access to other user's projects
        }
        $slug = Str::slug($request->name);
            // Create the new page
            $page = WebPage::create([
                'name' => $request->name,
                'page_id' => $request->page_id,
                'website_id' => $request->website_id,
                'main' => 0,  // Default is 0, as it won't be the main page initially
                'title' => $request->name . " - " . $project->project_name,
                'html' => '<body></body>',
                'css' => '* { box-sizing: border-box; } body {margin: 0;}',
                'slug' => $slug,
            ]);

            return response()->json(['success' => true, 'page' => $page]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function pageDelete(Request $request, $id)
    {
        // Find the page by ID
        $page = WebPage::where('page_id', $id)->where('website_id', $request->websiteId)->first();

        // Check if page exists
        if (!$page) {
            return response()->json(['success' => false, 'error' => 'Page not found'], 404);
        }

        // Optionally check if the page belongs to the logged-in user
        // if ($page->user_id !== auth()->id()) {
        //     return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        // }

        // Delete the page
        $page->delete();

        // Return success response
        return response()->json(['success' => true, 'page' => $page]);
    }



    public function pageRename(Request $request, $id)
    {
        // Find the page by ID
        $page = WebPage::where('page_id', $request->page_id)->where('website_id', $request->websiteId)->first();

        // Check if page exists
        if (!$page) {
            return response()->json(['success' => false, 'error' => 'Page not found'], 404);
        }

        // Optionally check if the page belongs to the logged-in user
        // if ($page->user_id !== auth()->id()) {
        //     return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        // }

        // Update the page name
        $page->name = $request->name;
        $page->save(); // Save the updated name

        // Return success response
        return response()->json(['success' => true, 'page' => $page]);
    }




    public function pageHtmlCss(Request $request)
    {
        $page = WebPage::where('page_id', $request->pageId)->where('website_id', $request->websiteId)->first();
        // If the page doesn't exist, return 404
        // Check if header and footer data exists
        if (!$page) {
            return response()->json(['error' => 'Page not found'], 404);
        }
        $page->html = $request->html; // Assuming `html_content` is the column name
        $page->css = $request->css; // Assuming `css_content` is the column name
        $page->save();

        return response()->json(['success' => true, 'message' => 'Page content saved successfully!']);
    }





    public function savePagesData(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'pages' => 'required|array',
            'pages.*.pageId' => 'required|exists:web_pages,page_id',
            'pages.*.html' => 'nullable|string',
            'pages.*.css' => 'nullable|string',
            'websiteId' => 'required|exists:projects_data,project_id',
        ]);

        
        $websiteId = $validated['websiteId'];
        $pagesData = $validated['pages'];

        $updatedPages = [];
        $failedPages = [];

        foreach ($pagesData as $pageData) {
            $page = WebPage::where('page_id', $pageData['pageId'])
                ->where('website_id', $websiteId)
                ->first();

            if ($page) {
                $page->html = $pageData['html'] ?? $page->html;
                $page->css = $pageData['css'] ?? $page->css;

                if ($page->save()) {
                    $updatedPages[] = $pageData['pageId'];
                } else {
                    $failedPages[] = $pageData['pageId'];
                }
            } else {
                $failedPages[] = $pageData['pageId'];
            }
        }

        return response()->json([
            'success' => true,
            'updatedPages' => $updatedPages,
            'failedPages' => $failedPages,
            'message' => count($failedPages) > 0
                ? 'Some pages could not be updated.'
                : 'All pages updated successfully!',
        ]);
    }



    public function headerEdit($project_id)
    {
        // Fetch the project from the database using the project_id
        $project = Project::find($project_id);

        // If the project doesn't exist, return 404
        if (!$project || $project->user_id != auth()->id()) {
            abort(404); // Prevent unauthorized access to other user's projects
        }

        // Load header and footer for the project (if not created, create defaults)
        $header = HeaderFooter::where("website_id", $project_id)
            ->where("is_header", true)
            ->first();



        // If no entry exists, create a new one, otherwise update the existing one
        if (!$header) {
            return response()->json([
                'status' => 'error',
                'message' => 'Header not found.'
            ], 404); // Not found
        }



        // Pass the project to the view
        return view('header-footer.index', [
            'project' => $project,
            'project_name' => $project->project_name,
            'project_id' => $project->project_id,
            'is_header' => $header->is_header,
        ]);
    }


    public function footerEdit($project_id)
    {
        // Fetch the project from the database using the project_id
        $project = Project::find($project_id);

        // If the project doesn't exist, return 404
        if (!$project || $project->user_id != auth()->id()) {
            abort(404); // Prevent unauthorized access to other user's projects
        }

        // Load header and footer for the project (if not created, create defaults)
        $footer = HeaderFooter::where("website_id", $project_id)
            ->where("is_header", false)
            ->first();

        // If no entry exists, create a new one, otherwise update the existing one
        if (!$footer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Footer not found.'
            ], 404); // Not found
        }

        // Pass the project to the view
        return view('header-footer.index', [
            'project' => $project,
            'project_name' => $project->project_name,
            'project_id' => $project->project_id,
            'is_header' => $footer->is_header,
        ]);
    }

    public function saveHeader($project_id, Request $request)
    {
        $headerJson = $request->getContent();

        // Make sure project_id and project_json are provided
        if (!$project_id || empty($headerJson)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Project ID or data not provided.'
            ], 400); // Bad request
        }

        // Find the header-footer entry for the project
        $header = HeaderFooter::where("website_id", $project_id)
            ->where("is_header", true)
            ->first();

        // If no entry exists, create a new one, otherwise update the existing one
        if (!$header) {
            return response()->json([
                'status' => 'error',
                'message' => 'Header & Footer not found.'
            ], 404); // Not found
        }

        // Update existing entry
        $header->update([
            'json' => $headerJson,
        ]);

        // Return a success response
        return response()->json(['message' => 'Header saved successfully!']);
    }

    public function saveFooter($project_id, Request $request)
    {
        $footerJson = $request->getContent();

        // Make sure project_id and project_json are provided
        if (!$project_id || empty($footerJson)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Project ID or data not provided.'
            ], 400); // Bad request
        }

        // Find the footer-footer entry for the project
        $footer = HeaderFooter::where("website_id", $project_id)
            ->where("is_header", false)
            ->first();

        // If no entry exists, create a new one, otherwise update the existing one
        if (!$footer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Footer not found.'
            ], 404); // Not found
        }

        // Update existing entry
        $footer->update([
            'json' => $footerJson,
        ]);

        // Return a success response
        return response()->json(['message' => 'Header saved successfully!']);
    }


    public function loadHeader($project_id)
    {
        // Find the header-footer entry for the project
        $header = HeaderFooter::where("website_id", $project_id)
            ->where("is_header", true)
            ->first();

        // Check if header and footer data exists
        if (!$header) {
            return response()->json(['error' => 'Header not found'], 404);
        }

        // Return the HTML and CSS data as JSON
        return response($header->json, 200)->header('Content-Type', 'application/json');
    }

    public function loadFooter($project_id)
    {
        // Find the header-footer entry for the project
        $footer = HeaderFooter::where("website_id", $project_id)
            ->where("is_header", false)
            ->first();

        // Check if header and footer data exists
        if (!$footer) {
            return response()->json(['error' => 'footer not found'], 404);
        }

        // Return the HTML and CSS data as JSON
        return response($footer->json, 200)->header('Content-Type', 'application/json');
    }


    public function headerData(Request $request)
    {
        $header = HeaderFooter::where("website_id", $request->project_id)
            ->where("is_header", true)
            ->first();
        // Check if header and footer data exists
        if (!$header) {
            return response()->json(['error' => 'Header not found'], 404);
        }
        $header->html = $request->html; // Assuming `html_content` is the column name
        $header->css = $request->css; // Assuming `css_content` is the column name
        $header->save();

        return response()->json(['success' => true, 'message' => 'Header content saved successfully!']);
    }


    public function footerData(Request $request)
    {
        $Footer = HeaderFooter::where("website_id", $request->project_id)
            ->where("is_header", false)
            ->first();
        // Check if Footer and footer data exists
        if (!$Footer) {
            return response()->json(['error' => 'Footer not found'], 404);
        }
        $Footer->html = $request->html; // Assuming `html_content` is the column name
        $Footer->css = $request->css; // Assuming `css_content` is the column name
        $Footer->save();

        return response()->json(['success' => true, 'message' => 'Footer content saved successfully!']);
    }


    public function previewFiles($project_id, $filename = 'index.html')
    {


        // Check if the authenticated user has access to this project
        $project = Project::find($project_id);

        if (!$project || $project->user_id != auth()->id()) {
            // If the project doesn't exist or the user is not the owner, abort with a 403 Forbidden error
            abort(404);
        }


        // Define the base path to the preview folder for the given project
        $basePath = resource_path('views/preview/' . $project_id);

        // Check if the requested file exists, if not, return index.html by default
        $filePath = $basePath . '/' . $filename;

        // Check if the file exists
        if (File::exists($filePath)) {
            // Return the contents of the requested file
            return response(File::get($filePath))
                ->header('Content-Type', $this->getContentType($filename));
        } else {
            // If the file doesn't exist, return a 404 error
            abort(404, 'File not found');
        }
    }

    // Helper function to determine the content type based on file extension
    private function getContentType($filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'html':
                return 'text/html';
            case 'css':
                return 'text/css';
            case 'js':
                return 'application/javascript';
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            case 'gif':
                return 'image/gif';
            default:
                return 'application/octet-stream';
        }
    }


}
