jQuery(document).ready(function($) {
    
    // Use event delegation for dynamically loaded content
    $(document).on('click', '#change-api-key', function(e) {
        e.preventDefault();
        console.log('Replace button clicked');
        $('#api-key-display').hide();
        $('#api-key-edit').show();
        $('#pmmm_google_api_key').focus();
    });
    
    // Cancel API key edit
    $(document).on('click', '#cancel-api-key', function(e) {
        e.preventDefault();
        console.log('Cancel button clicked');
        $('#api-key-edit').hide();
        $('#api-key-display').show();
        // Clear the input field
        $('#pmmm_google_api_key').val('');
    });
    
    // Remove API key
    $(document).on('click', '#remove-api-key', function(e) {
        e.preventDefault();
        console.log('Remove button clicked');
        if (confirm('Are you sure you want to remove the API key? Your maps will stop working until you add a new key.')) {
            $('#api-key-display').hide();
            $('#api-key-edit').show();
            $('#pmmm_google_api_key').val('DELETE_KEY');
            $(this).closest('form').submit();
        }
    });
    
    // Media uploader for custom marker
    $('#upload-marker-button').on('click', function(e) {
        e.preventDefault();
        
        var mediaUploader;
        
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Custom Marker Icon',
            button: {
                text: 'Use this icon'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#pmmm_custom_marker_url').val(attachment.url);
            
            // Show preview
            var preview = '<p>Preview: <img src="' + attachment.url + '" style="width: 40px; height: 40px; vertical-align: middle;" /></p>';
            $('#pmmm_custom_marker_url').closest('td').find('p:last').remove();
            $('#pmmm_custom_marker_url').closest('td').append(preview);
        });
        
        mediaUploader.open();
    });
    
    // Add new map functionality
    $('#add-new-map').on('click', function(e) {
        e.preventDefault();
        $('#new-map-form').slideDown();
    });
    
    $('#cancel-new-map').on('click', function(e) {
        e.preventDefault();
        $('#new-map-form').slideUp();
        $('#new-map-name').val('');
    });
    
    $('#save-new-map').on('click', function(e) {
        e.preventDefault();
        
        var mapName = $('#new-map-name').val().trim();
        
        if (!mapName) {
            alert('Please enter a map name');
            return;
        }
        
        $.ajax({
            url: pmmm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'pmmm_save_map',
                nonce: pmmm_admin.nonce,
                map_name: mapName
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error creating map');
                }
            }
        });
    });
    
    // Delete map functionality
    $('.delete-map').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this map and all its locations?')) {
            return;
        }
        
        var mapId = $(this).data('map-id');
        
        $.ajax({
            url: pmmm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'pmmm_delete_map',
                nonce: pmmm_admin.nonce,
                map_id: mapId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error deleting map');
                }
            }
        });
    });
    
    // Copy shortcode functionality
    $('.copy-shortcode').on('click', function(e) {
        e.preventDefault();
        
        var shortcode = $(this).data('shortcode');
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val(shortcode).select();
        document.execCommand('copy');
        $temp.remove();
        
        $(this).text('Copied!');
        setTimeout(() => {
            $(this).text('Copy');
        }, 2000);
    });
    
    // Map editor functionality
    if ($('#pmmm-map-editor').length) {
        var map;
        var markers = [];
        var mapId = $('#pmmm-map-editor').data('map-id');
        
        // Initialize map when Google Maps is loaded
        window.initAdminMap = function() {
            if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
                // Suppress Google Maps font loading errors
                var originalConsoleError = console.error;
                console.error = function() {
                    if (arguments[0] && arguments[0].toString().indexOf('fonts.gstatic.com') === -1) {
                        originalConsoleError.apply(console, arguments);
                    }
                };
                
                initializeAdminMap();
            }
        };
        
        // Try to initialize if Google Maps is already loaded
        if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
            initializeAdminMap();
        }
        
        function initializeAdminMap() {
            if (!document.getElementById('map-preview')) {
                console.log('Map preview element not found');
                return;
            }
            
            // Get default center from settings or use fallback
            var defaultLat = parseFloat(window.pmmmMapData.defaultLat) || 40.7128;
            var defaultLng = parseFloat(window.pmmmMapData.defaultLng) || -74.0060;
            
            var mapOptions = {
                zoom: 10,
                center: { lat: defaultLat, lng: defaultLng },
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                streetViewControl: false,
                mapTypeControl: false
            };
            
            try {
                map = new google.maps.Map(document.getElementById('map-preview'), mapOptions);
            } catch (e) {
                console.error('Error initializing map:', e);
                return;
            }
            
            // Autocomplete removed due to conflicts
            
            // Add existing locations to map
            if (window.pmmmMapData.locations) {
                window.pmmmMapData.locations.forEach(function(location) {
                    addMarkerToMap(location);
                });
                
                if (markers.length > 0) {
                    fitMapToBounds();
                }
            }
        }
        
        function addMarkerToMap(location) {
            var position = new google.maps.LatLng(
                parseFloat(location.latitude),
                parseFloat(location.longitude)
            );
            
            var marker = new google.maps.Marker({
                position: position,
                map: map,
                title: location.location_name,
                locationId: location.id
            });
            
            markers.push(marker);
            
            marker.addListener('click', function() {
                editLocation(location);
            });
        }
        
        function fitMapToBounds() {
            var bounds = new google.maps.LatLngBounds();
            markers.forEach(function(marker) {
                bounds.extend(marker.getPosition());
            });
            map.fitBounds(bounds);
        }
        
        function removeMarkerFromMap(locationId) {
            markers = markers.filter(function(marker) {
                if (marker.locationId == locationId) {
                    marker.setMap(null);
                    return false;
                }
                return true;
            });
        }
        
        
        // Edit location button
        $(document).on('click', '.edit-location', function() {
            var location = $(this).data('location');
            editLocation(location);
        });
        
        function editLocation(location) {
            $('#modal-title').text('Edit Location');
            $('#location-id').val(location.id);
            $('#location-name').val(location.location_name);
            $('#location-address').val(location.address);
            $('#location-lat').val(location.latitude);
            $('#location-lng').val(location.longitude);
            $('#location-phone').val(location.phone || '');
            $('#location-custom-text').val(location.custom_text || '');
            $('#location-custom-link').val(location.custom_link || '');
            $('#location-modal').show();
        }
        
        // Cancel location edit
        $('#cancel-location').on('click', function(e) {
            e.preventDefault();
            $('#location-modal').fadeOut();
        });
        
        // Remove close modal on outside click - only close with cancel button
        
        // Add location button
        $('#add-location').on('click', function(e) {
            e.preventDefault();
            console.log('Add location clicked');
            $('#modal-title').text('Add Location');
            $('#location-form')[0].reset();
            $('#location-id').val('');
            $('#location-modal').fadeIn();
        });
        
        // Geocode address using client-side geocoding
        $('#geocode-address').on('click', function(e) {
            e.preventDefault();
            var address = $('#location-address').val();
            
            if (!address) {
                alert('Please enter an address');
                return;
            }
            
            // Check if Google Maps is loaded
            if (!google || !google.maps || !google.maps.Geocoder) {
                alert('Google Maps is not loaded. Please check your API key.');
                return;
            }
            
            // Use Google Maps Geocoder (client-side)
            var geocoder = new google.maps.Geocoder();
            
            geocoder.geocode({ 'address': address }, function(results, status) {
                if (status === 'OK') {
                    if (results[0]) {
                        var location = results[0].geometry.location;
                        
                        // Update lat/lng fields
                        $('#location-lat').val(location.lat());
                        $('#location-lng').val(location.lng());
                        
                        // Update address with formatted address
                        $('#location-address').val(results[0].formatted_address);
                        
                        // Update map if available
                        if (map) {
                            map.setCenter(location);
                            map.setZoom(15);
                            
                            // Add a temporary marker
                            var tempMarker = new google.maps.Marker({
                                position: location,
                                map: map,
                                animation: google.maps.Animation.DROP
                            });
                            
                            // Remove marker after 3 seconds
                            setTimeout(function() {
                                tempMarker.setMap(null);
                            }, 3000);
                        }
                        
                        console.log('Geocoding successful:', results[0]);
                    } else {
                        alert('No results found for this address.');
                    }
                } else {
                    var errorMessage = 'Geocoding failed: ';
                    switch(status) {
                        case 'ZERO_RESULTS':
                            errorMessage += 'No results found for this address.';
                            break;
                        case 'OVER_QUERY_LIMIT':
                            errorMessage += 'Query limit exceeded. Try again later.';
                            break;
                        case 'REQUEST_DENIED':
                            errorMessage += 'Request denied. Check API key settings.';
                            break;
                        case 'INVALID_REQUEST':
                            errorMessage += 'Invalid address format.';
                            break;
                        default:
                            errorMessage += status;
                    }
                    alert(errorMessage);
                    console.error('Geocoding error:', status);
                }
            });
        });
        
        // Save location
        $('#location-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = {
                action: 'pmmm_save_location',
                nonce: pmmm_admin.nonce,
                location_id: $('#location-id').val(),
                map_id: mapId,
                location_name: $('#location-name').val(),
                address: $('#location-address').val(),
                latitude: $('#location-lat').val(),
                longitude: $('#location-lng').val(),
                phone: $('#location-phone').val(),
                custom_text: $('#location-custom-text').val(),
                custom_link: $('#location-custom-link').val()
            };
            
            $.ajax({
                url: pmmm_admin.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error saving location');
                    }
                }
            });
        });
        
        // Delete location
        $(document).on('click', '.delete-location', function() {
            if (!confirm('Are you sure you want to delete this location?')) {
                return;
            }
            
            var locationId = $(this).data('location-id');
            
            $.ajax({
                url: pmmm_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'pmmm_delete_location',
                    nonce: pmmm_admin.nonce,
                    location_id: locationId
                },
                success: function(response) {
                    if (response.success) {
                        $('.location-item[data-location-id="' + locationId + '"]').fadeOut(function() {
                            $(this).remove();
                        });
                        removeMarkerFromMap(locationId);
                    } else {
                        alert('Error deleting location');
                    }
                }
            });
        });
        
    }
});