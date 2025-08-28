<?php
/**
 * Plugin activation class
 */

class PMMM_Activator {
    
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        
        // Add rewrite endpoint for secure maps loader
        add_rewrite_endpoint('pmmm-maps-loader', EP_ROOT);
        flush_rewrite_rules();
    }
    
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Maps table
        $maps_table = $wpdb->prefix . 'pmmm_maps';
        $sql_maps = "CREATE TABLE $maps_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            map_name varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Locations table
        $locations_table = $wpdb->prefix . 'pmmm_locations';
        $sql_locations = "CREATE TABLE $locations_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            map_id int(11) NOT NULL,
            location_name varchar(255) NOT NULL,
            address text NOT NULL,
            latitude decimal(10, 8) NOT NULL,
            longitude decimal(11, 8) NOT NULL,
            phone varchar(50),
            custom_text text,
            custom_link varchar(255),
            marker_order int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY map_id (map_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_maps);
        dbDelta($sql_locations);
    }
    
    private static function set_default_options() {
        add_option('pmmm_google_api_key', '');
        add_option('pmmm_default_zoom', 13);
        add_option('pmmm_default_center_lat', 40.7128);
        add_option('pmmm_default_center_lng', -74.0060);
        add_option('pmmm_default_map_height', '400px');
    }
}