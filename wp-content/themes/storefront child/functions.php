<?php

// Enqueue parent theme styles
function storefront_child_enqueue_styles() {
    //enqueue styles
    wp_enqueue_style('storefront-parent-style', get_template_directory_uri() . '/style.css');

    //enqueue scripts and localize ajax
    wp_enqueue_script('ajax-search', get_stylesheet_directory_uri() . '/ajax-search.js', array('jquery'), null, true);
    wp_localize_script('ajax-search', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'storefront_child_enqueue_styles');

// Register Custom Post Type: Cities
function register_cities_post_type() {
    $args = array(
        'label' => __('Cities', 'textdomain'),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'menu_icon' => 'dashicons-location',
    );
    register_post_type('cities', $args);
}
add_action('init', 'register_cities_post_type');


// Register Custom Taxonomy: Countries
function register_countries_taxonomy() {
    $args = array(
        'label' => __('Countries', 'textdomain'),
        'public' => true,
        'hierarchical' => true,
    );
    register_taxonomy('countries', 'cities', $args);
}
add_action('init', 'register_countries_taxonomy');


// Add Meta Boxes for Latitude and Longitude
function cities_meta_boxes() {
    add_meta_box('cities_coordinates', __('City Coordinates', 'textdomain'), 'cities_coordinates_callback', 'cities', 'side', 'default');
}
add_action('add_meta_boxes', 'cities_meta_boxes');

function cities_coordinates_callback($post) {
    // Add nonce for security
    wp_nonce_field('cities_coordinates_nonce', 'cities_coordinates_nonce');

    // Retrieve existing values
    $latitude = get_post_meta($post->ID, 'latitude', true);
    $longitude = get_post_meta($post->ID, 'longitude', true);

    echo '<label for="latitude">' . __('Latitude', 'textdomain') . '</label>';
    echo '<input type="text" id="latitude" name="latitude" value="' . esc_attr($latitude) . '" />';
    
    echo '<label for="longitude">' . __('Longitude', 'textdomain') . '</label>';
    echo '<input type="text" id="longitude" name="longitude" value="' . esc_attr($longitude) . '" />';
}

// Save Meta Box Data
function save_cities_meta_boxes($post_id) {
    // Check nonce for security
    if (!isset($_POST['cities_coordinates_nonce']) || !wp_verify_nonce($_POST['cities_coordinates_nonce'], 'cities_coordinates_nonce')) {
        return;
    }

    // Save Latitude
    if (isset($_POST['latitude'])) {
        update_post_meta($post_id, 'latitude', sanitize_text_field($_POST['latitude']));
    }

    // Save Longitude
    if (isset($_POST['longitude'])) {
        update_post_meta($post_id, 'longitude', sanitize_text_field($_POST['longitude']));
    }
}
add_action('save_post', 'save_cities_meta_boxes');


function get_temperature_func($lat, $lon) {
    // Replace with your OpenWeatherMap API key
    $api_key = 'c37e79cae6b2c1a562cd5f318f9f5176'; // Use your API key here
    $url = "http://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&units=metric&appid=$api_key";
    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return __('Error fetching temperature', 'textdomain');
    }
    $data = json_decode(wp_remote_retrieve_body($response), true);
    return isset($data['main']['temp']) ? $data['main']['temp'] : __('N/A', 'textdomain');
}


// AJAX handler for city search
function ajax_city_search() {
    if (isset($_POST['search_term'])) {
        $search_term = sanitize_text_field($_POST['search_term']);
        error_log('Search term received: ' . $search_term); // Log the search term

        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, p.post_title, t.name AS country_name 
            FROM {$wpdb->posts} p 
            JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id) 
            JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) 
            JOIN {$wpdb->terms} t ON (tt.term_id = t.term_id) 
            WHERE p.post_type = 'cities' 
            AND p.post_status = 'publish' 
            AND p.post_title LIKE %s", '%' . $wpdb->esc_like($search_term) . '%'));

        // Log the results for debugging
        if ($results) {
            error_log('Results found: ' . print_r($results, true)); // Log the actual results

            foreach ($results as $city) {
                $lat = get_post_meta($city->ID, 'latitude', true);
                $lon = get_post_meta($city->ID, 'longitude', true);

                $temperature = get_temperature_func($lat, $lon);
                
               // Get terms associated with the post
                $terms = get_the_terms($city->ID, 'countries'); // Replace 'countries' with your custom taxonomy slug if different
                $term_names = [];

                if ($terms && !is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $term_names[] = esc_html($term->name); // Collect term names
                    }
                }
                
                // Convert term names array to a comma-separated string
                $terms_list = implode(', ', $term_names);

                echo '<tr>';
                echo '<td>' . esc_html($terms_list) . '</td>'; // Display terms
                echo '<td>' . esc_html($city->post_title) . '</td>';
                echo '<td>' . esc_html($temperature) . '°C</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="3">' . __('No results found', 'textdomain') . '</td></tr>';
            error_log('No results found for the search term: ' . $search_term); // Log no results
        }
    } else {
        echo '<tr><td colspan="3">' . __('No search term provided', 'textdomain') . '</td></tr>';
        error_log('No search term provided'); // Log no search term
    }

    wp_die(); // Required to terminate and return a proper response
}
add_action('wp_ajax_city_search', 'ajax_city_search');
add_action('wp_ajax_nopriv_city_search', 'ajax_city_search');