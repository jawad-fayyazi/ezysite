<?php
namespace App\Traits;

use App\Models\Project;
use App\Models\PrivateTemplate;
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
            'pro' => 50
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
        $storageLimit = $roles->contains('premium') ? 1024 * 1024 * 1024 : 5 * 1024 * 1024; // 1GB or 100MB

        // Calculate total storage used
        $usedStorage = $this->calculateUserStorage($user);

        // Calculate remaining storage
        $remainingStorage = $storageLimit - $usedStorage;

        // Check if the user can upload the file
        if ($fileSize > $remainingStorage) {
            return [
                "status" => "danger",
                "title" => [$remainingStorage / 1048576, $usedStorage / 1048576, $storageLimit / 1048576],
                "body" => "You do not have enough storage to upload this file."
            ];
        }

        return [
            "status" => "success",
            "title" => "Upload Allowed",
            "body" => "You have enough storage to upload this file."
        ];
    }

    // Function to calculate total storage used by the user
    private function calculateUserStorage($user)
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
    private function getFolderSize($folderPath)
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
}
