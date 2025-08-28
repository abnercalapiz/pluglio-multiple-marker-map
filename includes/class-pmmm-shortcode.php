<?php
/**
 * Shortcode functionality
 */

class PMMM_Shortcode {
    
    public function __construct() {
        add_shortcode('pmmm_map', array($this, 'render_map'));
    }
    
    public function render_map($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'height' => get_option('pmmm_default_map_height', '400px'),
            'width' => '100%',
            'zoom' => get_option('pmmm_default_zoom', 13)
        ), $atts);
        
        $map_id = intval($atts['id']);
        
        if (!$map_id) {
            return '<p>Please specify a map ID</p>';
        }
        
        global $wpdb;
        
        // Get map data
        $map = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pmmm_maps WHERE id = %d",
            $map_id
        ));
        
        if (!$map) {
            return '<p>Map not found</p>';
        }
        
        // Get locations
        $locations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pmmm_locations WHERE map_id = %d ORDER BY marker_order, id",
            $map_id
        ));
        
        if (empty($locations)) {
            return '<p>No locations found for this map</p>';
        }
        
        // Check if API key is set
        $api_key = get_option('pmmm_google_api_key');
        if (!$api_key) {
            return '<p>Google Maps API key not configured</p>';
        }
        
        // Generate unique ID for this map instance
        $map_instance_id = 'pmmm-map-' . $map_id . '-' . uniqid();
        
        ob_start();
        ?>
        <div id="<?php echo esc_attr($map_instance_id); ?>" 
             class="pmmm-map-container"
             data-map-id="<?php echo esc_attr($map_id); ?>"
             data-zoom="<?php echo esc_attr($atts['zoom']); ?>"
             style="height: <?php echo esc_attr($atts['height']); ?>; width: <?php echo esc_attr($atts['width']); ?>;">
            <div class="pmmm-map-loading">Loading map...</div>
        </div>
        
        <script>
            if (!window.pmmmMapsData) {
                window.pmmmMapsData = {};
            }
            window.pmmmMapsData['<?php echo esc_js($map_instance_id); ?>'] = {
                locations: <?php echo json_encode($locations); ?>,
                mapId: <?php echo $map_id; ?>,
                zoom: <?php echo intval($atts['zoom']); ?>
            };
        </script>
        <?php
        
        return ob_get_clean();
    }
}