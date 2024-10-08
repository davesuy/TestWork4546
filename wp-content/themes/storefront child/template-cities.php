<?php
/* Template Name: Cities Table */

// Include the widget file
require_once get_stylesheet_directory() . '/widget-cities.php'; // Adjusted to correctly reference the child theme

get_header(); ?>

<div class="container">
    <h1><?php the_title(); ?></h1>
    <input type="text" id="city-search" placeholder="Search for cities..." />
    
    <?php do_action('before_cities_table'); // Before Table Hook ?>

    <table id="cities-table">
        <thead>
            <tr>
                <th><?php _e('Country', 'textdomain'); ?></th>
                <th><?php _e('City', 'textdomain'); ?></th>
                <th><?php _e('Temperature (°C)', 'textdomain'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            global $wpdb;
            $results = $wpdb->get_results("SELECT p.ID, p.post_title, t.name AS country_name FROM {$wpdb->posts} p JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id) JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) JOIN {$wpdb->terms} t ON (tt.term_id = t.term_id) WHERE p.post_type = 'cities' AND p.post_status = 'publish'");

            // Create an instance of Cities_Widget
            $cities_widget = new Cities_Widget();

            foreach ($results as $city) {
                $lat = get_post_meta($city->ID, 'latitude', true);
                $lon = get_post_meta($city->ID, 'longitude', true);
                $temperature = $cities_widget->get_temperature($lat, $lon); // Call the method from the widget
                echo '<tr>';
                echo '<td>' . esc_html($city->country_name) . '</td>';
                echo '<td>' . esc_html($city->post_title) . '</td>';
                echo '<td>' . esc_html($temperature) . '°C</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>

    <?php do_action('after_cities_table'); // After Table Hook ?>
</div>

<script>
    document.getElementById('city-search').addEventListener('keyup', function() {
        var input = this.value.toLowerCase();
        var table = document.getElementById('cities-table');
        var rows = table.getElementsByTagName('tr');

        for (var i = 1; i < rows.length; i++) { // Skip header row
            var cityCell = rows[i].getElementsByTagName('td')[1];
            if (cityCell) { // Check if cityCell exists
                var city = cityCell.textContent.toLowerCase();
                rows[i].style.display = city.includes(input) ? '' : 'none';
            } else {
                rows[i].style.display = 'none'; // Hide the row if cityCell is undefined
            }
        }
    });
</script>


<?php get_footer(); ?>