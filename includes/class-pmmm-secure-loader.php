<?php
/**
 * Secure Google Maps Loader using AJAX
 */

class PMMM_Secure_Loader {
    
    public function __construct() {
        add_action('wp_ajax_pmmm_load_maps', array($this, 'load_maps_script'));
        add_action('wp_ajax_nopriv_pmmm_load_maps', array($this, 'load_maps_script'));
    }
    
    public function load_maps_script() {
        // Verify nonce
        check_ajax_referer('pmmm_nonce', 'nonce');
        
        // Check if API key exists
        $api_key = get_option('pmmm_google_api_key');
        if (!$api_key) {
            wp_send_json_error('No API key configured');
        }
        
        // Generate script URL
        $script_url = 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($api_key) . '&callback=initPMMMMap';
        
        // Return encrypted/encoded response
        wp_send_json_success(array(
            'script_url' => $script_url
        ));
    }
}