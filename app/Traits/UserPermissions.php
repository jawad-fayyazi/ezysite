<?php
namespace App\Traits;

use App\Models\Project;
use App\Models\PrivateTemplate;
use App\Models\WebPage;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;


trait UserPermissions {

    /**
     * Main function to check permissions dynamically
     * @param string $role The role of the user (e.g., 'basic', 'premium')
     * @param string $permission The action to check (e.g., 'canCreateWebsite')
     * @return array Associative array with status, title, and body
     */
    public function canDo($role, $permission) {
        if (method_exists($this, $permission)) {
            return $this->$permission($role);
        }
        return [
            "status" => "danger",
            "title" => "Permission Error",
            "body" => "Invalid permission check requested."
        ];
    }

    /**
     * Check if a user can create a website
     * @param string $role The role of the user
     * @return array
     */
    private function canCreateWebsite($role) {
        $maxWebsites = [
            'registered' => 2,
            'basic' => 1,
            'premium' => 10,
            'pro' => 50,
            'admin' => 100000000000000000
        ];

        $userWebsiteCount = $this->projects()->count(); // Assuming a relation

        if (!isset($maxWebsites[$role])) {
            return [
                "status" => "danger",
                "title" => "Role Error",
                "body" => "Invalid user role."
            ];
        }

        if ($userWebsiteCount >= $maxWebsites[$role]) {
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
     * Check if a user can use custom domains
     * @param string $role The role of the user
     * @return array
     */
    private function canUseCustomDomain($role) {
        if (!in_array($role, ['premium', 'pro'])) {
            return [
                "status" => "danger",
                "title" => "Domain Error",
                "body" => "You cannot use custom domains on the basic plan."
            ];
        }

        return [
            "status" => "success",
            "title" => "Allowed",
            "body" => "You can use a custom domain."
        ];
    }

    /**
     * Check if a user can use premium templates
     * @param string $role The role of the user
     * @return array
     */
    private function canUsePremiumTemplates($role) {
        if ($role === 'basic') {
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

        // Define storage limits based on roles
        $storageLimits = [
            'registered' => 100 * 1024 * 1024,  // 100MB
            'basic' => 500 * 1024 * 1024,       // 500MB
            'premium' => 500 * 1024 * 1024,     // 500MB
            'pro' => 500 * 1024 * 1024,         // 500MB
            'admin' => 1024 * 1024 * 1024,      // 1GB
        ];


        // Get the role (assuming first role in collection)
        $role = $roles->first();

        // Check if the role exists in the limits
        if (!isset($storageLimits[$role])) {
            return [
                "status" => "danger",
                "title" => "Role Error",
                "body" => "Invalid user role."
            ];
        }

        // Get the appropriate storage limit for the user's role
        $storageLimit = $storageLimits[$role];
        
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
        $maxPages = [
            'registered' => 5,   // 5 pages
            'basic' => 10,       // 10 pages
            'premium' => 50,     // 50 pages
            'pro' => 100,        // 100 pages
            'admin' => 100000000000000000, // Admin: No restriction
        ];

        // Get the role (assuming first role in collection)
        $role = $roles->first();
        
        
        // Get the user's current page count (assuming a relation to pages)
        $userPageCount = WebPage::where("website_id", $website_id)->count();
        
        // Check if the user's role is valid and has an assigned page limit
        if (!isset($maxPages[$role])) {
            return [
                "status" => "danger",
                "title" => "Role Error",
                "body" => "Invalid user role."
            ];
        }

        // Check if the user has reached their page limit
        if ($userPageCount >= $maxPages[$role]) {
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
