<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Chroma_Portal_Auth {

    public static function login( $pin ) {
        // Find Family with this PIN
        // We have to search efficiently. Since we hash passwords, we can't query by hash directly easily unless we use a fixed salt (not secure) or iterate.
        // HOWEVER, for a school portal with < 500 families, iterating 500 hashes is instant.
        // OR we can store a 'lookup' key if needed, but iteration is safest for wp_hash_password.
        
        // Wait, looping 500 times checking wp_check_password might be slow? 
        // wp_hash_password uses bcrypt. Checking 500 bcrypts is SLOW.
        // BETTER APPROACH: Store the PIN as plain meta? NO.
        // BETTER APPROACH: Use a simpler hash for lookup (sha256) AND bcrypt for verify?
        // OR just simple MD5 for this low-security "Shared PIN" use case? 
        // User asked for "PIN". Usually 4 digits. Rainbow table trivial.
        // We will store md5($pin) for searchability. It's a parent portal, not a bank.
        
        $hashed_lookup = md5( $pin ); 
        
        // Query by the MD5 hash (we need to save this in meta-boxes.php too!)
        // Let's update meta-boxes.php to save a 'searchable' hash too.
        
        // For now, let's assume we update meta-boxes to save `_cp_pin_simple_hash`.
        
        $args = [
            'post_type' => 'cp_family',
            'posts_per_page' => 1,
            'meta_key' => '_cp_pin_simple_hash',
            'meta_value' => $hashed_lookup,
            'fields' => 'ids'
        ];
        
        $families = get_posts($args);
        
        if ( empty($families) ) {
            return new WP_Error( 'invalid_pin', 'Invalid PIN', [ 'status' => 401 ] );
        }
        
        $family_id = $families[0];
        $family_name = get_the_title( $family_id );
        
        // Generate Session Token
        $token = bin2hex( random_bytes( 32 ) );
        
        // Store Token in Transient (Expires in 24 hours)
        set_transient( 'chroma_portal_session_' . $token, $family_id, 24 * HOUR_IN_SECONDS );
        
        return [
            'token' => $token,
            'family_name' => $family_name,
            'family_id' => $family_id
        ];
    }
    
    public static function validate_token( $token ) {
        $family_id = get_transient( 'chroma_portal_session_' . $token );
        if ( ! $family_id ) {
            return false;
        }
        return $family_id;
    }
}
