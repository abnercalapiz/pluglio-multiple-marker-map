<?php
/**
 * Google Maps Script Loader
 * Loads Google Maps API dynamically to hide API key from frontend
 */

class PMMM_Maps_Loader {
    
    public function __construct() {
        add_action('init', array($this, 'register_endpoint'));
        add_action('template_redirect', array($this, 'handle_maps_script'));
    }
    
    public function register_endpoint() {
        add_rewrite_endpoint('pmmm-maps-loader', EP_ROOT);
    }
    
    public function handle_maps_script() {
        // Check if this is our endpoint
        $request_uri = $_SERVER['REQUEST_URI'];
        if (strpos($request_uri, '/pmmm-maps-loader/') === false) {
            return;
        }
        
        // Verify request is coming from same domain
        $referer = wp_get_referer();
        $current_host = $_SERVER['HTTP_HOST'];
        
        // Allow both with and without www
        $allowed_hosts = array(
            $current_host,
            'www.' . $current_host,
            str_replace('www.', '', $current_host)
        );
        
        if ($referer) {
            $referer_host = parse_url($referer, PHP_URL_HOST);
            if (!in_array($referer_host, $allowed_hosts)) {
                wp_die('Unauthorized referer', 403);
            }
        }
        
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'pmmm_maps_loader')) {
            wp_die('Invalid nonce', 403);
        }
        
        $api_key = get_option('pmmm_google_api_key');
        
        if (!$api_key) {
            wp_die('API key not configured', 500);
        }
        
        // Set JavaScript content type
        header('Content-Type: application/javascript');
        header('Cache-Control: private, max-age=3600');
        
        // Output Google Maps loader script
        ?>
        // Load Google Maps without exposing the API key
        (function() {
            window.pmmmLoadGoogleMaps = function() {
                var script = document.createElement('script');
                script.src = 'https://maps.googleapis.com/maps/api/js?key=<?php echo esc_js($api_key); ?>&callback=initPMMMMap';
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            };
            
            // Auto-load after a short delay to ensure page is ready
            setTimeout(window.pmmmLoadGoogleMaps, 100);
        })();
        <?php
        exit;
    }
}