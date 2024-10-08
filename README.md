# TestWork4546

**Test Work AbeloHost | Full-stack Developer (PHP / WordPress) Code Documentation**

## Code Documentation

This project is built as a child theme for the Storefront theme. Below are the key implementations and code documentation for the project:

### 1. Child Theme Setup

- A child theme for the Storefront theme was created to manage all custom modifications.

### 2. Custom Post Type: Cities

- A custom post type called **“Cities”** was created using hooks.
  - Code Location: `functions.php` (Lines 14-25)

### 3. Meta Boxes for Latitude and Longitude

- A meta box with custom fields **“latitude”** and **“longitude”** was added for entering the respective coordinates of the city.
  - Code Location: `functions.php` (Lines 40-78)

### 4. Custom Taxonomy: Countries

- A custom taxonomy titled **“Countries”** was created and attached to the **“Cities”** post type.
  - Code Location: `functions.php` (Lines 28-37)

### 5. Cities Widget

- A widget was developed to display city names along with their current temperatures fetched from an external API (e.g., OpenWeatherMap).
  - File Created: `widget-cities.php`

### 6. Custom Template for Countries and Cities Table

- A custom template was created to display a table listing countries, cities, and temperatures.
  - File Created: `template-cities.php`
  - Data is retrieved using a database query with the global variable `$wpdb`.
  - A search field for cities was added above the table using WP Ajax, along with custom action hooks before and after the table.
  - Code Location: `functions.php` (Lines 81-152)

## App Demonstration

You can view a demonstration of the application [here](https://www.loom.com/share/9807aa6dc1664c64bda83ae2725a89d7).
