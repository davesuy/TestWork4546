# TestWork4546

Test Work AbeloHost | Full-stack Developer (PHP / WordPress) Code Documentation

# Storefront Child Theme

This project is a custom WordPress child theme built upon the Storefront theme. The modifications made in this child theme include custom post types, taxonomies, AJAX functionality for city searches, and a widget for displaying city temperatures.

## Table of Contents

- [Project Overview](#project-overview)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [File Structure](#file-structure)
- [Code Documentation](#code-documentation)

## Project Overview

The child theme allows users to manage and display cities with their corresponding temperatures using the OpenWeatherMap API. The following features are included:

- Custom post type for cities.
- Custom taxonomy for countries.
- Meta boxes for city coordinates (latitude and longitude).
- AJAX functionality for real-time city search.
- Widget to display current temperature for a selected city.

## Installation

1. Clone or download the repository to your local environment.
2. Create a new directory in the `wp-content/themes/` folder of your WordPress installation for the child theme.
3. Copy the contents of this project into the newly created child theme directory.
4. Activate the child theme from the WordPress admin dashboard.

## Usage

- After activation, you can create new cities using the WordPress admin interface under the "Cities" section.
- Use the provided widget to display the current temperature of cities in the sidebar or any widget area.
- The temperature data is fetched from the OpenWeatherMap API based on the latitude and longitude entered for each city.

## File Structure

```plaintext
storefront-child/
│
├── functions.php         # Enqueues styles and scripts, registers custom post types and taxonomies, AJAX handler
├── style.css             # Child theme styles
├── ajax-search.js        # JavaScript for AJAX city search functionality
├── template-cities.php   # Template for displaying cities and their temperatures
└── widget-cities.php     # Custom widget for displaying city temperatures
```

## Code Documentation

## functions.php

<?php

// Enqueue parent theme styles and scripts
function storefront_child_enqueue_styles() {
    wp_enqueue_style('storefront-parent-style', get_template_directory_uri() . '/style.css');
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

// Callback function for displaying meta box fields
function cities_coordinates_callback($post) {
    wp_nonce_field('cities_coordinates_nonce', 'cities_coordinates_nonce');
    $latitude = get_post_meta($post->ID, 'latitude', true);
    $longitude = get_post_meta($post->ID, 'longitude', true);
    echo '<label for="latitude">' . __('Latitude', 'textdomain') . '</label>';
    echo '<input type="text" id="latitude" name="latitude" value="' . esc_attr($latitude) . '" />';
    echo '<label for="longitude">' . __('Longitude', 'textdomain') . '</label>';
    echo '<input type="text" id="longitude" name="longitude" value="' . esc_attr($longitude) . '" />';
}

// Save Meta Box Data
function save_cities_meta_boxes($post_id) {
    if (!isset($_POST['cities_coordinates_nonce']) || !wp_verify_nonce($_POST['cities_coordinates_nonce'], 'cities_coordinates_nonce')) {
        return;
    }
    if (isset($_POST['latitude'])) {
        update_post_meta($post_id, 'latitude', sanitize_text_field($_POST['latitude']));
    }
    if (isset($_POST['longitude'])) {
        update_post_meta($post_id, 'longitude', sanitize_text_field($_POST['longitude']));
    }
}
add_action('save_post', 'save_cities_meta_boxes');

// Function to fetch temperature from OpenWeatherMap API
function get_temperature_func($lat, $lon) {
    $api_key = 'your_api_key_here'; // Use your API key here
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

        if ($results) {
            foreach ($results as $city) {
                $lat = get_post_meta($city->ID, 'latitude', true);
                $lon = get_post_meta($city->ID, 'longitude', true);
                $temperature = get_temperature_func($lat, $lon);
                $terms = get_the_terms($city->ID, 'countries');
                $term_names = [];
                if ($terms && !is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $term_names[] = esc_html($term->name);
                    }
                }
                $terms_list = implode(', ', $term_names);
                echo '<tr>';
                echo '<td>' . esc_html($terms_list) . '</td>';
                echo '<td>' . esc_html($city->post_title) . '</td>';
                echo '<td>' . esc_html($temperature) . '°C</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="3">' . __('No results found', 'textdomain') . '</td></tr>';
        }
    } else {
        echo '<tr><td colspan="3">' . __('No search term provided', 'textdomain') . '</td></tr>';
    }
    wp_die(); // Required to terminate and return a proper response
}
add_action('wp_ajax_city_search', 'ajax_city_search');
add_action('wp_ajax_nopriv_city_search', 'ajax_city_search');


## ajax-search.js

jQuery(document).ready(function($) {
    $('#city-search').on('keyup', function() {
        var searchTerm = $(this).val();
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'city_search',
                search_term: searchTerm
            },
            success: function(response) {
                $('#cities-table tbody').html(response);
            }
        });
    });
});


## style.css

/*
    Theme Name: Storefront Child
    Template: storefront
*/


## widget-cities.php

<?php
class Cities_Widget extends WP_Widget {
    function __construct() {
        parent::__construct('cities_widget', __('Cities Temperature', 'textdomain'), array('description' => __('Displays a city and its current temperature', 'textdomain')));
    }

    public function widget($args, $instance) {
        $cities = get_posts(array('post_type' => 'cities', 'numberposts' => 1));
        if ($cities) {
            foreach ($cities as $city) {
                $city_name = $city->post_title;
                $latitude = get_post_meta($city->ID, 'latitude', true);
                $longitude = get_post_meta($city->ID, 'longitude', true);
                $temperature = get_temperature_func($latitude, $longitude);
                echo '<div class="city-widget">';
                echo '<h3>' . esc_html($city_name) . '</h3>';
                echo '<p>' . esc_html($temperature) . '°C</p>';
                echo '</div>';
            }
        }
    }
}
function register_cities_widget() {
    register_widget('Cities_Widget');
}
add_action('widgets_init', 'register_cities_widget');
