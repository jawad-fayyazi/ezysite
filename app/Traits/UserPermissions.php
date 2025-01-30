<?php
namespace App\Traits;

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
}
