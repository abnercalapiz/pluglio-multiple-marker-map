<?php
/**
 * Admin functionality
 */

class PMMM_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_pmmm_save_map', array($this, 'ajax_save_map'));
        add_action('wp_ajax_pmmm_delete_map', array($this, 'ajax_delete_map'));
        add_action('wp_ajax_pmmm_save_location', array($this, 'ajax_save_location'));
        add_action('wp_ajax_pmmm_delete_location', array($this, 'ajax_delete_location'));
        add_action('wp_ajax_pmmm_geocode_address', array($this, 'ajax_geocode_address'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Pluglio Maps',
            'Pluglio Maps',
            'manage_options',
            'pmmm-maps',
            array($this, 'maps_page'),
            'dashicons-location-alt',
            30
        );
        
        add_submenu_page(
            'pmmm-maps',
            'All Maps',
            'All Maps',
            'manage_options',
            'pmmm-maps',
            array($this, 'maps_page')
        );
        
        add_submenu_page(
            'pmmm-maps',
            'Settings',
            'Settings',
            'manage_options',
            'pmmm-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'pmmm-') === false) {
            return;
        }
        
        wp_enqueue_script('pmmm-admin', PMMM_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), PMMM_VERSION . '.1', true);
        wp_enqueue_style('pmmm-admin', PMMM_PLUGIN_URL . 'assets/css/admin.css', array(), PMMM_VERSION);
        
        // Enqueue media uploader on settings page
        if ($hook === 'pluglio-maps_page_pmmm-settings') {
            wp_enqueue_media();
        }
        
        wp_localize_script('pmmm-admin', 'pmmm_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pmmm_admin_nonce')
        ));
        
        // Enqueue Google Maps for location editing
        if (strpos($hook, 'pmmm-maps') !== false && isset($_GET['action']) && $_GET['action'] === 'edit') {
            $api_key = get_option('pmmm_google_api_key');
            if ($api_key) {
                wp_enqueue_script(
                    'google-maps-admin',
                    'https://maps.googleapis.com/maps/api/js?key=' . esc_attr($api_key) . '&callback=initAdminMap',
                    array('jquery'),
                    null,
                    true
                );
            }
        }
    }
    
    public function settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('pmmm_save_settings');
            
            update_option('pmmm_google_api_key', sanitize_text_field($_POST['pmmm_google_api_key']));
            update_option('pmmm_default_zoom', intval($_POST['pmmm_default_zoom']));
            update_option('pmmm_default_center_lat', floatval($_POST['pmmm_default_center_lat']));
            update_option('pmmm_default_center_lng', floatval($_POST['pmmm_default_center_lng']));
            update_option('pmmm_custom_marker_url', esc_url_raw($_POST['pmmm_custom_marker_url']));
            update_option('pmmm_default_map_height', sanitize_text_field($_POST['pmmm_default_map_height']));
            
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $api_key = get_option('pmmm_google_api_key');
        $default_zoom = get_option('pmmm_default_zoom', 13);
        $default_center_lat = get_option('pmmm_default_center_lat', 40.7128);
        $default_center_lng = get_option('pmmm_default_center_lng', -74.0060);
        $custom_marker_url = get_option('pmmm_custom_marker_url', '');
        $default_map_height = get_option('pmmm_default_map_height', '400px');
        ?>
        <div class="wrap">
            <h1>Pluglio Maps Settings</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('pmmm_save_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="pmmm_google_api_key">Google Maps API Key</label>
                        </th>
                        <td>
                            <input type="text" id="pmmm_google_api_key" name="pmmm_google_api_key" 
                                   value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                            <?php if ($api_key) : ?>
                                <p class="description" style="color: green; margin-top: 5px;">
                                    <strong>✓ API Key is configured</strong>
                                </p>
                            <?php endif; ?>
                            <p class="description">
                                Enter your Google Maps API key. 
                                <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">
                                    Get an API key
                                </a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pmmm_default_zoom">Default Zoom Level</label>
                        </th>
                        <td>
                            <input type="number" id="pmmm_default_zoom" name="pmmm_default_zoom" 
                                   value="<?php echo esc_attr($default_zoom); ?>" min="1" max="20" />
                            <p class="description">Default zoom level for maps (1-20)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label>Default Center Location</label>
                        </th>
                        <td>
                            <label>Latitude: </label>
                            <input type="text" name="pmmm_default_center_lat" 
                                   value="<?php echo esc_attr($default_center_lat); ?>" />
                            <label style="margin-left: 20px;">Longitude: </label>
                            <input type="text" name="pmmm_default_center_lng" 
                                   value="<?php echo esc_attr($default_center_lng); ?>" />
                            <p class="description">Default center coordinates for new maps</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pmmm_default_map_height">Default Map Height</label>
                        </th>
                        <td>
                            <input type="text" id="pmmm_default_map_height" name="pmmm_default_map_height" 
                                   value="<?php echo esc_attr($default_map_height); ?>" />
                            <p class="description">Default height for frontend maps (e.g., 400px, 500px, 100vh)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="pmmm_custom_marker_url">Custom Map Pin Icon URL</label>
                        </th>
                        <td>
                            <input type="url" id="pmmm_custom_marker_url" name="pmmm_custom_marker_url" 
                                   value="<?php echo esc_attr($custom_marker_url); ?>" class="regular-text" />
                            <button type="button" class="button" id="upload-marker-button">Upload Image</button>
                            <p class="description">
                                Enter URL for custom map marker icon (36x36 pixels recommended). 
                                Leave empty to use default red marker.
                            </p>
                            <?php if ($custom_marker_url) : ?>
                                <p>Preview: <img src="<?php echo esc_attr($custom_marker_url); ?>" 
                                              style="width: 40px; height: 40px; vertical-align: middle;" /></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function maps_page() {
        global $wpdb;
        
        // Handle map editing
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['map_id'])) {
            $this->edit_map_page();
            return;
        }
        
        // Get all maps
        $maps = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}pmmm_maps ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">All Maps</h1>
            <a href="#" class="page-title-action" id="add-new-map">Add New</a>
            
            <div id="new-map-form" style="display: none; margin-top: 20px; padding: 20px; background: #fff; border: 1px solid #ddd;">
                <h2>Create New Map</h2>
                <p>
                    <label for="new-map-name">Map Name:</label>
                    <input type="text" id="new-map-name" class="regular-text" />
                    <button class="button button-primary" id="save-new-map">Create Map</button>
                    <button class="button" id="cancel-new-map">Cancel</button>
                </p>
            </div>
            
            <?php if (empty($maps)) : ?>
                <p>No maps found. Create your first map!</p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Map Name</th>
                            <th>Shortcode</th>
                            <th>Locations</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maps as $map) : 
                            $location_count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}pmmm_locations WHERE map_id = %d",
                                $map->id
                            ));
                        ?>
                        <tr>
                            <td><?php echo esc_html($map->map_name); ?></td>
                            <td>
                                <code>[pmmm_map id="<?php echo $map->id; ?>"]</code>
                                <button class="button-link copy-shortcode" data-shortcode='[pmmm_map id="<?php echo $map->id; ?>"]'>
                                    Copy
                                </button>
                            </td>
                            <td><?php echo $location_count; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($map->created_at)); ?></td>
                            <td>
                                <a href="?page=pmmm-maps&action=edit&map_id=<?php echo $map->id; ?>" class="button-link">
                                    Edit
                                </a>
                                |
                                <a href="#" class="button-link delete-map" data-map-id="<?php echo $map->id; ?>">
                                    Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function edit_map_page() {
        global $wpdb;
        
        $map_id = intval($_GET['map_id']);
        $map = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pmmm_maps WHERE id = %d",
            $map_id
        ));
        
        if (!$map) {
            echo '<div class="wrap"><h1>Map not found</h1></div>';
            return;
        }
        
        $locations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pmmm_locations WHERE map_id = %d ORDER BY marker_order, id",
            $map_id
        ));
        
        $api_key = get_option('pmmm_google_api_key');
        ?>
        <div class="wrap">
            <h1>Edit Map: <?php echo esc_html($map->map_name); ?></h1>
            <p><a href="?page=pmmm-maps">&larr; Back to All Maps</a></p>
            
            <?php if (!$api_key) : ?>
                <div class="notice notice-warning">
                    <p>Please configure your Google Maps API key in <a href="?page=pmmm-settings">Settings</a> to use the map features.</p>
                </div>
            <?php endif; ?>
            
            <div id="pmmm-map-editor" data-map-id="<?php echo $map_id; ?>">
                <div class="pmmm-editor-row">
                    <div class="pmmm-locations-panel">
                        <h2>Locations</h2>
                        <button class="button button-primary" id="add-location">Add Location</button>
                        
                        <div id="locations-list">
                            <?php foreach ($locations as $location) : ?>
                                <div class="location-item" data-location-id="<?php echo $location->id; ?>">
                                    <h3><?php echo esc_html($location->location_name); ?></h3>
                                    <p><?php echo esc_html($location->address); ?></p>
                                    <?php if ($location->phone) : ?>
                                        <p>Phone: <?php echo esc_html($location->phone); ?></p>
                                    <?php endif; ?>
                                    <button class="button edit-location" data-location='<?php echo esc_attr(json_encode($location)); ?>'>
                                        Edit
                                    </button>
                                    <button class="button delete-location" data-location-id="<?php echo $location->id; ?>">
                                        Delete
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="pmmm-map-preview">
                        <h2>Map Preview</h2>
                        <div id="map-preview" style="height: 500px; background: #f0f0f0;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Location Edit Modal -->
            <div id="location-modal" class="pmmm-modal" style="display: none;">
                <div class="pmmm-modal-content">
                    <h2 id="modal-title">Add Location</h2>
                    <form id="location-form">
                        <input type="hidden" id="location-id" value="">
                        <input type="hidden" id="location-map-id" value="<?php echo $map_id; ?>">
                        
                        <p>
                            <label for="location-name">Location Name *</label>
                            <input type="text" id="location-name" class="widefat" required>
                        </p>
                        
                        <p>
                            <label for="location-address">Address *</label>
                            <input type="text" id="location-address" class="widefat" required>
                            <button type="button" class="button" id="geocode-address">Get Coordinates</button>
                        </p>
                        
                        <p>
                            <label for="location-lat">Latitude *</label>
                            <input type="text" id="location-lat" class="widefat" required>
                        </p>
                        
                        <p>
                            <label for="location-lng">Longitude *</label>
                            <input type="text" id="location-lng" class="widefat" required>
                        </p>
                        
                        <p>
                            <label for="location-phone">Phone</label>
                            <input type="text" id="location-phone" class="widefat">
                        </p>
                        
                        <p>
                            <label for="location-custom-text">Custom Text</label>
                            <textarea id="location-custom-text" class="widefat" rows="3"></textarea>
                        </p>
                        
                        <p>
                            <label for="location-custom-link">Custom Link</label>
                            <input type="url" id="location-custom-link" class="widefat">
                        </p>
                        
                        <p>
                            <button type="submit" class="button button-primary">Save Location</button>
                            <button type="button" class="button" id="cancel-location">Cancel</button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
            var pmmmMapData = {
                mapId: <?php echo $map_id; ?>,
                locations: <?php echo json_encode($locations); ?>,
                apiKey: '<?php echo esc_js($api_key); ?>',
                defaultLat: <?php echo get_option('pmmm_default_center_lat', 40.7128); ?>,
                defaultLng: <?php echo get_option('pmmm_default_center_lng', -74.0060); ?>
            };
            
        </script>
        <?php
    }
    
    // Ajax handlers
    public function ajax_save_map() {
        check_ajax_referer('pmmm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $map_name = sanitize_text_field($_POST['map_name']);
        
        $wpdb->insert(
            $wpdb->prefix . 'pmmm_maps',
            array('map_name' => $map_name),
            array('%s')
        );
        
        wp_send_json_success(array(
            'map_id' => $wpdb->insert_id,
            'message' => 'Map created successfully'
        ));
    }
    
    public function ajax_delete_map() {
        check_ajax_referer('pmmm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $map_id = intval($_POST['map_id']);
        
        // Delete locations first
        $wpdb->delete($wpdb->prefix . 'pmmm_locations', array('map_id' => $map_id), array('%d'));
        
        // Delete map
        $wpdb->delete($wpdb->prefix . 'pmmm_maps', array('id' => $map_id), array('%d'));
        
        wp_send_json_success(array('message' => 'Map deleted successfully'));
    }
    
    public function ajax_save_location() {
        check_ajax_referer('pmmm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $location_data = array(
            'map_id' => intval($_POST['map_id']),
            'location_name' => sanitize_text_field($_POST['location_name']),
            'address' => sanitize_text_field($_POST['address']),
            'latitude' => floatval($_POST['latitude']),
            'longitude' => floatval($_POST['longitude']),
            'phone' => sanitize_text_field($_POST['phone']),
            'custom_text' => sanitize_textarea_field($_POST['custom_text']),
            'custom_link' => esc_url_raw($_POST['custom_link'])
        );
        
        $location_id = intval($_POST['location_id']);
        
        if ($location_id) {
            // Update existing location
            $wpdb->update(
                $wpdb->prefix . 'pmmm_locations',
                $location_data,
                array('id' => $location_id),
                array('%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s'),
                array('%d')
            );
        } else {
            // Insert new location
            $wpdb->insert(
                $wpdb->prefix . 'pmmm_locations',
                $location_data,
                array('%d', '%s', '%s', '%f', '%f', '%s', '%s', '%s')
            );
            $location_id = $wpdb->insert_id;
        }
        
        $location = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pmmm_locations WHERE id = %d",
            $location_id
        ));
        
        wp_send_json_success(array(
            'location' => $location,
            'message' => 'Location saved successfully'
        ));
    }
    
    public function ajax_delete_location() {
        check_ajax_referer('pmmm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $location_id = intval($_POST['location_id']);
        
        $wpdb->delete(
            $wpdb->prefix . 'pmmm_locations',
            array('id' => $location_id),
            array('%d')
        );
        
        wp_send_json_success(array('message' => 'Location deleted successfully'));
    }
    
    public function ajax_geocode_address() {
        check_ajax_referer('pmmm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $address = sanitize_text_field($_POST['address']);
        $api_key = get_option('pmmm_google_api_key');
        
        if (!$api_key) {
            wp_send_json_error('Google Maps API key not configured');
        }
        
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . '&key=' . $api_key;
        
        $args = array(
            'timeout' => 30,
            'sslverify' => false, // Some hosts have SSL issues
            'headers' => array(
                'Referer' => home_url()
            )
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            error_log('Geocode error: ' . $response->get_error_message());
            wp_send_json_error('Failed to connect to Google Maps API: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid response from Google Maps API');
        }
        
        if (!isset($data['status'])) {
            wp_send_json_error('Unexpected response format from Google Maps API');
        }
        
        // Log the API response status for debugging
        if ($data['status'] !== 'OK') {
            error_log('Google Maps API status: ' . $data['status']);
            if (isset($data['error_message'])) {
                error_log('Google Maps API error: ' . $data['error_message']);
            }
        }
        
        switch ($data['status']) {
            case 'OK':
                if (!empty($data['results'])) {
                    $location = $data['results'][0]['geometry']['location'];
                    wp_send_json_success(array(
                        'lat' => $location['lat'],
                        'lng' => $location['lng'],
                        'formatted_address' => $data['results'][0]['formatted_address']
                    ));
                } else {
                    wp_send_json_error('No results found for this address');
                }
                break;
            case 'ZERO_RESULTS':
                wp_send_json_error('No results found for this address. Please try a more specific address.');
                break;
            case 'OVER_QUERY_LIMIT':
                wp_send_json_error('API quota exceeded. Please try again later.');
                break;
            case 'REQUEST_DENIED':
                wp_send_json_error('API request denied. Please check your API key configuration and restrictions.');
                break;
            case 'INVALID_REQUEST':
                wp_send_json_error('Invalid address format. Please check and try again.');
                break;
            default:
                wp_send_json_error('Geocoding failed: ' . ($data['error_message'] ?? $data['status']));
        }
    }
}