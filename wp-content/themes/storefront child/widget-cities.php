<?php
class Cities_Widget extends WP_Widget {
    function __construct() {
        parent::__construct('cities_widget', __('Cities Temperature', 'textdomain'), array('description' => __('Displays a city and its current temperature', 'textdomain')));
    }

    public function widget($args, $instance) {
        $cities = get_posts(array('post_type' => 'cities', 'numberposts' => 1)); // Adjust as needed
        if ($cities) {
            foreach ($cities as $city) {
                $city_name = $city->post_title;
                $latitude = get_post_meta($city->ID, 'latitude', true);
                $longitude = get_post_meta($city->ID, 'longitude', true);
                $temperature = $this->get_temperature($latitude, $longitude); // Call the method defined in the class

                echo $args['before_widget'];
                echo $args['before_title'] . $city_name . $args['after_title'];
                echo '<p>Current Temperature: ' . esc_html($temperature) . '°C</p>';
                echo $args['after_widget'];
            }
        }
    }

    function get_temperature($lat, $lon) {
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
}

// Register the Widget
function register_cities_widget() {
    register_widget('Cities_Widget');
}
add_action('widgets_init', 'register_cities_widget');