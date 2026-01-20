<?php
/**
 * Chroma Media Permissions
 * 
 * Grants upload_files capability to users with a valid Chroma session token.
 * This allows Directors (authenticated via Google) to use the WordPress Media Library.
 */

class Chroma_Media_Permissions
{
    public function __construct()
    {
        add_filter('user_has_cap', [$this, 'grant_upload_cap'], 10, 4);
        add_filter('wp_ajax_nopriv_query-attachments', [$this, 'allow_media_query']);
    }

    /**
     * Check if the current request has a valid Chroma session.
     */
    private function get_chroma_session()
    {
        // Check for session token in cookie
        $token = $_COOKIE['chroma_session'] ?? '';
        
        if (empty($token)) {
            return null;
        }

        // Validate session transient
        $session = get_transient('chroma_sess_' . $token);
        
        if (!$session || empty($session['school_id'])) {
            return null;
        }

        // Check expiry
        if (isset($session['exp']) && $session['exp'] < time()) {
            return null;
        }

        return $session;
    }

    /**
     * Grant upload_files capability to users with valid Chroma session.
     */
    public function grant_upload_cap($allcaps, $caps, $args, $user)
    {
        // Only intercept if checking for upload_files or edit_posts
        $media_caps = ['upload_files', 'edit_posts'];
        
        if (empty(array_intersect($caps, $media_caps))) {
            return $allcaps;
        }

        // Check for valid Chroma session
        $session = $this->get_chroma_session();
        
        if ($session) {
            // Grant media capabilities
            $allcaps['upload_files'] = true;
            $allcaps['edit_posts'] = true;
        }

        return $allcaps;
    }

    /**
     * Allow non-logged-in users with Chroma session to query attachments.
     */
    public function allow_media_query()
    {
        $session = $this->get_chroma_session();
        
        if ($session) {
            // Spoof a minimal user context for the media library
            // This is necessary because wp.media requires some user context
            add_filter('determine_current_user', function($user_id) {
                // Return admin user ID for media queries
                // This is safe because we've already validated the Chroma session
                return 1;
            }, 999);
        }
    }
}
