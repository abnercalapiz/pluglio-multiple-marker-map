var pmmmMaps = {};


function initPMMMMap() {
    // Initialize all maps on the page
    jQuery('.pmmm-map-container').each(function() {
        var containerId = jQuery(this).attr('id');
        var mapData = window.pmmmMapsData[containerId];
        
        if (mapData && mapData.locations.length > 0) {
            initializeMap(containerId, mapData);
        }
    });
}

function initializeMap(containerId, mapData) {
    var bounds = new google.maps.LatLngBounds();
    var mapOptions = {
        zoom: parseInt(mapData.zoom) || 13,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        styles: [
            {
                featureType: "poi",
                elementType: "labels",
                stylers: [{ visibility: "off" }]
            }
        ],
        // Disable default UI to prevent font loading issues
        disableDefaultUI: false,
        zoomControl: true,
        mapTypeControl: false,
        scaleControl: true,
        streetViewControl: false,
        rotateControl: false,
        fullscreenControl: true
    };
    
    var map = new google.maps.Map(document.getElementById(containerId), mapOptions);
    pmmmMaps[containerId] = map;
    
    var markers = [];
    var infoWindows = [];
    
    // Custom marker icon
    var markerIcon = null;
    if (pmmm_ajax.custom_marker && pmmm_ajax.custom_marker !== '') {
        markerIcon = {
            url: pmmm_ajax.custom_marker,
            scaledSize: new google.maps.Size(36, 36),
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(18, 36)
        };
    } else {
        markerIcon = {
            url: pmmm_ajax.plugin_url + 'assets/images/marker.svg',
            scaledSize: new google.maps.Size(36, 36),
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(18, 18)
        };
    }
    
    // Create markers
    mapData.locations.forEach(function(location, index) {
        var position = new google.maps.LatLng(
            parseFloat(location.latitude),
            parseFloat(location.longitude)
        );
        
        bounds.extend(position);
        
        var marker = new google.maps.Marker({
            position: position,
            map: map,
            title: location.location_name,
            animation: google.maps.Animation.DROP,
            icon: markerIcon
        });
        
        markers.push(marker);
        
        // Create detailed info window content
        var infoContent = '<div class="pmmm-info-window">' +
            '<h3>' + escapeHtml(location.location_name) + '</h3>' +
            '<p class="pmmm-address">' + escapeHtml(location.address) + '</p>';
        
        if (location.phone) {
            infoContent += '<p class="pmmm-phone"><strong>Phone:</strong> ' + escapeHtml(location.phone) + '</p>';
        }
        
        if (location.custom_text) {
            infoContent += '<div class="pmmm-custom-text">' + escapeHtml(location.custom_text);
            
            if (location.custom_link) {
                infoContent += ' <a href="' + escapeHtml(location.custom_link) + '" target="_blank">Learn More</a>';
            }
            
            infoContent += '</div>';
        }
        
        infoContent += '<div class="pmmm-directions">' +
            '<a href="https://www.google.com/maps/dir/?api=1&destination=' + 
            location.latitude + ',' + location.longitude + '" target="_blank" class="pmmm-directions-link">Get Directions</a>' +
            '</div></div>';
        
        var infoWindow = new google.maps.InfoWindow({
            content: infoContent,
            maxWidth: 300
        });
        
        infoWindows.push(infoWindow);
        
        // Marker click event for detailed info
        marker.addListener('click', function() {
            // Close all other info windows
            infoWindows.forEach(function(iw) {
                iw.close();
            });
            
            infoWindow.open(map, marker);
        });
    });
    
    // Fit map to bounds
    if (markers.length > 1) {
        map.fitBounds(bounds);
        
        // Add padding
        var listener = google.maps.event.addListener(map, "idle", function() { 
            if (map.getZoom() > 16) map.setZoom(16); 
            google.maps.event.removeListener(listener); 
        });
    } else if (markers.length === 1) {
        map.setCenter(markers[0].getPosition());
        map.setZoom(parseInt(mapData.zoom) || 15);
    }
    
    // Remove loading indicator
    jQuery('#' + containerId + ' .pmmm-map-loading').remove();
}

function escapeHtml(text) {
    if (!text) return '';
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Initialize when Google Maps is loaded
window.initPMMMMap = initPMMMMap;

// Also initialize on document ready if Google Maps is already loaded
jQuery(document).ready(function() {
    if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
        initPMMMMap();
    }
});