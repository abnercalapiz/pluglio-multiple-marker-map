# Pluglio Multiple Marker Map

A WordPress plugin for displaying Google Maps with multiple markers and location details.

## Features

- **Multiple Maps Management** - Create and manage unlimited maps
- **Multiple Markers per Map** - Add unlimited location markers to each map
- **Custom Map Markers** - Upload custom marker icons (36x36 pixels)
- **Detailed Information Windows** - Display location details on marker click
- **Custom Location Fields**:
  - Location Name
  - Address (with client-side geocoding)
  - Phone
  - Custom Text with optional link
- **Google Maps Integration**:
  - Simple API key configuration
  - Client-side address geocoding (works with HTTP referrer restrictions)
  - Get directions link for each location
- **Shortcode Support** - Easy embedding with customizable parameters
- **Responsive Design** - Works on all devices
- **Clean Admin Interface** - User-friendly backend management
- **Media Library Integration** - Upload marker icons directly from WordPress
- **Secure API Key Management** - API key visible only to administrators

## Installation

1. Upload the `pluglio-multiple-marker-map` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your Google Maps API key in Pluglio Maps > Settings

## Getting Started

### 1. Configure Plugin Settings

1. Go to **Pluglio Maps > Settings**
2. Enter your **Google Maps API key** (Required)
3. Set **Default Zoom Level** (1-20, default: 13)
4. Set **Default Center Location** coordinates
5. Set **Default Map Height** (e.g., 400px, 500px, 100vh)
6. Optionally upload a **Custom Map Pin Icon** (36x36 pixels recommended)
7. Save settings

Need an API key? [Get one here](https://developers.google.com/maps/documentation/javascript/get-api-key)

**Required Google Maps APIs:**
- Maps JavaScript API (with Geocoding enabled)

**API Key Restrictions:**
- Use "HTTP referrers (web sites)" restriction
- Add your website URLs (e.g., `https://yourdomain.com/*`)

### 2. Create a Map

1. Go to Pluglio Maps > All Maps
2. Click "Add New"
3. Enter a map name
4. Click "Create Map"

### 3. Add Locations

1. Click "Edit" on your map
2. Click "Add Location"
3. Fill in the location details:
   - Location Name (required)
   - Address (required)
   - Latitude/Longitude (click "Get Coordinates" to auto-fill)
   - Phone (optional)
   - Custom Text (optional)
   - Custom Link (optional)
4. Save the location

### 4. Display the Map

Use the shortcode to display your map:

```
[pmmm_map id="1"]
```

Optional parameters:
- `height` - Map height (default: 400px)
- `width` - Map width (default: 100%)
- `zoom` - Initial zoom level (default: 13)

Example:
```
[pmmm_map id="1" height="500px" width="100%" zoom="15"]
```

## Customization

### Custom Marker Icons
- Recommended size: 36x36 pixels
- Supported formats: PNG, JPG, SVG
- Upload via Settings page or provide direct URL
- Default marker: Red circular icon

### Map Styling
The plugin includes clean, minimal styling that works with most themes. You can override styles by targeting these CSS classes:
- `.pmmm-map-container` - Main map container
- `.pmmm-info-window` - Information popup window
- `.pmmm-directions-link` - Get directions button

## Troubleshooting

### Map not displaying?
1. Check that your Google Maps API key is valid
2. Ensure Maps JavaScript API is enabled in Google Cloud Console
3. Check browser console for JavaScript errors

### Geocoding not working?
1. Ensure Maps JavaScript API is enabled in Google Cloud Console
2. Check that your domain is added to HTTP referrer restrictions
3. Try different address formats (e.g., "123 Main St, City, State" or "City, Country")

### Console font errors?
The "roboto-regular.woff2" error is harmless and can be ignored. It's a known Google Maps issue that doesn't affect functionality.

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Google Maps API key with Maps JavaScript API enabled

## Changelog

### Version 1.0.1
- Improved geocoding to use client-side JavaScript (works with HTTP referrer restrictions)
- Added default map height setting
- Enhanced API key security in admin interface
- Fixed map loading issues
- Removed autocomplete due to conflicts
- Better error handling for geocoding

### Version 1.0.0
- Initial release
- Multiple maps and markers support
- Custom marker icons
- Address geocoding
- Responsive design
- Admin interface
- Shortcode support

## Author

**Jezweb**  
Website: [https://jezweb.com.au](https://jezweb.com.au)

## License

GPL v2 or later