<?php
/**
 * AJAX handlers for frontend
 */

class PMMM_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_pmmm_get_map_data', array($this, 'get_map_data'));
        add_action('wp_ajax_nopriv_pmmm_get_map_data', array($this, 'get_map_data'));
        add_action('wp_ajax_pmmm_get_maps_config', array($this, 'get_maps_config'));
        add_action('wp_ajax_nopriv_pmmm_get_maps_config', array($this, 'get_maps_config'));
    }
    
    public function get_map_data() {
        check_ajax_referer('pmmm_nonce', 'nonce');
        
        $map_id = intval($_POST['map_id']);
        
        if (!$map_id) {
            wp_send_json_error('Invalid map ID');
        }
        
        global $wpdb;
        
        $locations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pmmm_locations WHERE map_id = %d ORDER BY marker_order, id",
            $map_id
        ));
        
        wp_send_json_success(array(
            'locations' => $locations
        ));
    }
    
    public function get_maps_config() {
        check_ajax_referer('pmmm_nonce', 'nonce');
        
        $api_key = get_option('pmmm_google_api_key');
        
        if (!$api_key) {
            wp_send_json_error('Google Maps API key not configured');
        }
        
        // Generate a unique session token for this request
        $session_token = wp_generate_password(32, false);
        set_transient('pmmm_maps_session_' . $session_token, true, 5 * MINUTE_IN_SECONDS);
        
        wp_send_json_success(array(
            'session_token' => $session_token,
            'callback_url' => admin_url('admin-ajax.php')
        ));
    }
}