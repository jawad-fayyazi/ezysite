<?php
namespace App\Traits;

use App\Models\Project;
use App\Models\PrivateTemplate;
use App\Models\WebPage;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;


trait UserPermissions
{


    public $storageLimits = [
        'registered' => 100 * 1024 * 1024,  // 100MB
        'basic' => 500 * 1024 * 1024,       // 500MB
        'premium' => 500 * 1024 * 1024,     // 500MB
        'pro' => 500 * 1024 * 1024,         // 500MB
        'admin' => 1024 * 1024 * 1024,      // 1GB
    ];

    public $maxWebsites = [
            'registered' => 2,
            'basic' => 1,
            'premium' => 10,
            'pro' => 50,
            'admin' => 100000000000000000
        ];
    public $maxPages = [
            'registered' => 5,   // 5 pages
            'basic' => 10,       // 10 pages
            'premium' => 50,     // 50 pages
            'pro' => 100,        // 100 pages
            'admin' => 100000000000000000, // Admin: No restriction
        ];




    /**
     * Check if a user can create a website
     * @param string $role The role of the user
     * @return array
     */
    public function canCreateWebsite($user)
    {


        $roles = $user->getRoleNames(); // Get user roles
        // Get the role (assuming first role in collection)
        $role = $roles->first();


        $userWebsiteCount = $this->projects()->count(); // Assuming a relation

        if (!isset($this->maxWebsites[$role])) {
            return [
                "status" => "danger",
                "title" => "Role Error",
                "body" => "Invalid user role."
            ];
        }

        if ($userWebsiteCount >= $this->maxWebsites[$role]) {
            return [
                "status" => "danger",
                "title" => "Website Limit Reached",
                "body" => "You have reached the maximum number of websites for your plan."
            ];
        }

        return [
            "status" => "success",
            "title" => "Allowed",
            "body" => "You can create a new website."
        ];
    }

    /**
     * Check if a user can create a website
     * @param string $role The role of the user
     * @return array
     */
    public function canCreateTemplate($user)
    {
        $maxTemplates = [
            'registered' => 1,
            'basic' => 1,
            'premium' => 10,
            'pro' => 50,
            'admin' => 100000000000000000
        ];

        $roles = $user->getRoleNames(); // Get user roles
        // Get the role (assuming first role in collection)
        $role = $roles->first();

        $userTemplateCount = $this->privateTemplates()->count(); // Assuming a relation

        if (!isset($maxTemplates[$role])) {
            return [
                "status" => "danger",
                "title" => "Role Error",
                "body" => "Invalid user role."
            ];
        }

        if ($userTemplateCount >= $maxTemplates[$role]) {
            return [
                "status" => "danger",
                "title" => "Template Limit Reached",
                "body" => "You have reached the maximum number of templates for your plan."
            ];
        }

        return [
            "status" => "success",
            "title" => "Allowed",
            "body" => "You can create a new template."
        ];
    }

    /**
     * Check if a user can use custom domains
     * @param string $user The user
     * @return array
     */
    public function canUseCustomDomain($user)
    {


        $roles = $user->getRoleNames(); // Get user roles
        // Get the role (assuming first role in collection)
        $role = $roles->first();

        $allowedRoles = [
            'basic',
            'premium',
            'pro',
            'admin'
        ];
        if (!in_array($role, $allowedRoles)) {
            return [
                "status" => "danger",
                "title" => "Domain Error",
                "body" => "You cannot use custom domains on this plan."
            ];
        }

        return [
            "status" => "success",
            "title" => "Allowed",
            "body" => "You can use a custom domain."
        ];
    }

    /**
     * Check if a user can use custom domains
     * @param string $user The user
     * @return array
     */
    public function shouldBranding($user)
    {


        $roles = $user->getRoleNames(); // Get user roles
        // Get the role (assuming first role in collection)
        $role = $roles->first();

        $allowedRoles = [
            'basic',
            'premium',
            'pro',
            'admin'
        ];
        if (!in_array($role, $allowedRoles)) {
            return [
                "status" => "danger",
                "title" => "Not allowed",
                "body" => "You cannot use without branding on this plan."
            ];
        }

        return [
            "status" => "success",
            "title" => "Allowed",
            "body" => "You can use without branding."
        ];
    }

    /**
     * Check if a user can use premium templates
     * @param string $role The role of the user
     * @return array
     */
    public function canUsePremiumTemplates($user)
    {
        $roles = $user->getRoleNames(); // Get user roles
        // Get the role (assuming first role in collection)
        $role = $roles->first();

        if ($role === 'registered') {
            return [
                "status" => "danger",
                "title" => "Premium Template Access Denied",
                "body" => "Upgrade to a premium plan to access premium templates."
            ];
        }

        return [
            "status" => "success",
            "title" => "Allowed",
            "body" => "You can use premium templates."
        ];
    }



    // Function to check if user can upload based on their storage usage
    public function canUploadFile($user, $fileSize)
    {
        $roles = $user->getRoleNames(); // Get user roles

        // Get the role (assuming first role in collection)
        $role = $roles->first();

        // Check if the role exists in the limits
        if (!isset($this->storageLimits[$role])) {
            return [
                "status" => "danger",
                "title" => "Role Error",
                "body" => "Invalid user role."
            ];
        }

        // Get the appropriate storage limit for the user's role
        $storageLimit = $this->storageLimits[$role];

        // Calculate total storage used
        $usedStorage = $this->calculateUserStorage($user);

        // Calculate remaining storage
        $remainingStorage = $storageLimit - $usedStorage;

        // Check if the user can upload the file
        if ($fileSize > $remainingStorage) {
            $neededStorage = $fileSize - $remainingStorage; // Calculate how much more storage is needed
            return [
                "status" => "danger",
                "title" => "Storage Limit Reached",
                "body" => "You do not have enough storage to upload this file. You need an additional " . $this->formatFileSize($neededStorage) . " of storage."
            ];
        }

        return [
            "status" => "success",
            "title" => "Upload Allowed",
            "body" => "You have enough storage to upload this file."
        ];
    }

    // Helper function to format file size in human-readable format (e.g., KB, MB, GB)
    public function formatFileSize($size)
    {
        if ($size >= 1024 * 1024 * 1024) {
            return number_format($size / (1024 * 1024 * 1024), 2) . ' GB';
        } elseif ($size >= 1024 * 1024) {
            return number_format($size / (1024 * 1024), 2) . ' MB';
        } elseif ($size >= 1024) {
            return number_format($size / 1024, 2) . ' KB';
        } else {
            return $size . ' Bytes';
        }
    }

    public function convertToBytes($value)
    {
        // Match the number and the unit
        if (preg_match('/(\d+(\.\d+)?)\s*(gb|mb|kb|bytes)?/i', $value, $matches)) {
            $number = (float) $matches[1];
            $unit = strtolower($matches[3] ?? 'bytes'); // Default to bytes if no unit

            switch ($unit) {
                case 'gb':
                    return $number * 1024 * 1024 * 1024;
                case 'mb':
                    return $number * 1024 * 1024;
                case 'kb':
                    return $number * 1024;
                case 'bytes':
                default:
                    return $number;
            }
        }
        return 0; // Return 0 if the format is invalid
    }

    // Function to calculate total storage used by the user
    public function calculateUserStorage($user)
    {
        $totalSize = 0;

        // Fetch user's projects
        $projects = Project::where('user_id', $user->id)->get();

        // Calculate size of project folders
        foreach ($projects as $project) {
            $projectPath = "/var/www/ezysite/public/storage/usersites/{$project->project_id}/";
            $totalSize += $this->getFolderSize($projectPath);
        }

        // Fetch user's private templates
        $privateTemplates = PrivateTemplate::where('user_id', $user->id)->get();

        // Calculate size of private template folders
        foreach ($privateTemplates as $template) {
            $templatePath = "/var/www/ezysite/public/storage/private-templates/{$template->id}/";
            $totalSize += $this->getFolderSize($templatePath);
        }

        // Filter live websites from fetched projects
        $liveWebsites = $projects->where('live', 1);

        // Calculate size of live website folders
        foreach ($liveWebsites as $website) {
            $domainPath = "/var/www/domain/{$website->domain}/";
            $totalSize += $this->getFolderSize($domainPath);
        }

        return $totalSize;
    }


    // Function to get the size of a folder
    public function getFolderSize($folderPath)
    {
        if (!file_exists($folderPath)) {
            return 0;
        }

        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folderPath, FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    /**
     * Check if a user can create a page
     * @param string $role The role of the user
     * @param string $website_id The role of the user
     * @return array
     */
    public function canCreatePage($user, $website_id)
    {
        $roles = $user->getRoleNames(); // Get user roles

        // Define maximum pages allowed for each role
        // Get the role (assuming first role in collection)
        $role = $roles->first();


        // Get the user's current page count (assuming a relation to pages)
        $userPageCount = WebPage::where("website_id", $website_id)->count();

        // Check if the user's role is valid and has an assigned page limit
        if (!isset($this->maxPages[$role])) {
            return [
                "status" => "danger",
                "title" => "Role Error",
                "body" => "Invalid user role."
            ];
        }

        // Check if the user has reached their page limit
        if ($userPageCount >= $this->maxPages[$role]) {
            return [
                "status" => "danger",
                "title" => "Page Limit Reached",
                "body" => "You have reached the maximum number of pages for your plan."
            ];
        }

        return [
            "status" => "success",
            "title" => "Allowed",
            "body" => "You can create a new page."
        ];
    }

}
