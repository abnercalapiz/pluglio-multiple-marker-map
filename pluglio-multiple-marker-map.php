<?php
/**
 * Plugin Name: Pluglio Multiple Marker Map
 * Plugin URI: https://jezweb.com.au
 * Description: Display Google Maps with multiple markers, tooltips, and location details
 * Version: 1.0.0
 * Author: Jezweb
 * Author URI: https://jezweb.com.au
 * License: GPL v2 or later
 * Text Domain: pluglio-multiple-marker-map
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PMMM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PMMM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PMMM_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('PMMM_VERSION', '1.0.0');

// Include required files
require_once PMMM_PLUGIN_PATH . 'includes/class-pmmm-activator.php';
require_once PMMM_PLUGIN_PATH . 'includes/class-pmmm-deactivator.php';
require_once PMMM_PLUGIN_PATH . 'includes/class-pmmm-admin.php';
require_once PMMM_PLUGIN_PATH . 'includes/class-pmmm-shortcode.php';
require_once PMMM_PLUGIN_PATH . 'includes/class-pmmm-ajax.php';
require_once PMMM_PLUGIN_PATH . 'includes/class-pmmm-maps-loader.php';
require_once PMMM_PLUGIN_PATH . 'includes/class-pmmm-secure-loader.php';

// Activation/Deactivation hooks
register_activation_hook(__FILE__, array('PMMM_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('PMMM_Deactivator', 'deactivate'));

// Initialize plugin
add_action('plugins_loaded', 'pmmm_init');
function pmmm_init() {
    // Initialize admin
    if (is_admin()) {
        new PMMM_Admin();
    }
    
    // Initialize shortcode
    new PMMM_Shortcode();
    
    // Initialize AJAX handlers
    new PMMM_Ajax();
    
    // Initialize Maps Loader
    new PMMM_Maps_Loader();
    
    // Initialize Secure Loader
    new PMMM_Secure_Loader();
}

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'pmmm_enqueue_frontend_assets');
function pmmm_enqueue_frontend_assets() {
    // Only enqueue if shortcode is present
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'pmmm_map')) {
        $api_key = get_option('pmmm_google_api_key');
        
        if ($api_key) {
            // Temporarily reverting to direct loading
            // TODO: Fix secure loader endpoint
            wp_enqueue_script(
                'google-maps',
                'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($api_key) . '&callback=initPMMMMap',
                array(),
                null,
                true
            );
        }
        
        wp_enqueue_script(
            'pmmm-frontend',
            PMMM_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            PMMM_VERSION,
            true
        );
        
        wp_enqueue_style(
            'pmmm-frontend',
            PMMM_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            PMMM_VERSION
        );
        
        wp_localize_script('pmmm-frontend', 'pmmm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pmmm_nonce'),
            'plugin_url' => PMMM_PLUGIN_URL,
            'custom_marker' => get_option('pmmm_custom_marker_url', '')
        ));
    }
}