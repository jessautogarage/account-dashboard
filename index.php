<?php
/*
 * Plugin Name:       Avocado Tutors Page Plugin
 * Description:       Manage pages, profile, tables to all of the users
 * Version:           1.0
 * Author:            Jesrel A.
 * License:           GPL v2 or later
 */

 if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class MyPluginLoader {
    /**
     * Define plugin directory path
     */
    private $plugin_path;

    /**
     * Constructor: Initialize paths and load files
     */
    public function __construct() {
        $this->plugin_path = plugin_dir_path(__FILE__);

        // Load all necessary files
        $this->load_all_files('tutor/');
        $this->load_all_files('student/');
        $this->load_all_files('parent/');
        $this->load_all_files('templates/');
        $this->load_all_files('profile/');
        $this->load_all_files('login/');
    }

    /**
     * Load all PHP files from a directory
     */
    private function load_all_files($directory) {
        $full_path = $this->plugin_path . $directory;

        if (is_dir($full_path)) {
            $files = glob($full_path . '*.php'); // Get all .php files in the directory

            if (!empty($files)) {
                foreach ($files as $file) {
                    require_once $file;
                }
            }
        }
    }
}

// Instantiate the class
new MyPluginLoader();

function enqueue_profile_style() {
    wp_enqueue_style( 'profile-css', plugins_url( 'assets/css/profile.css', __FILE__ ));
    wp_enqueue_style( 'availability-css', plugins_url( 'assets/css/availability.css', __FILE__ ));
    
    //wp_enqueue_script( 'peerjs', 'https://unpkg.com/peerjs@1.4.7/dist/peerjs.min.js', array(), null, true );
}
add_action( 'wp_enqueue_scripts', 'enqueue_profile_style' );

function custom_add_roles() {
    // Add Student Role (Read Only)
    add_role('student', 'Student', array(
        'read' => true, // Read-only permission
    ));

    // Add Parent Role (Read Only)
    add_role('parent', 'Parent', array(
        'read' => true, // Read-only permission
    ));

    // Add Teacher Role (Read Only)
    add_role('tutor', 'Teacher', array(
        'read' => true, // Read-only permission
    ));
}
add_action('init', 'custom_add_roles');

// Redirect after login based on user role
function login_redirect_by_user($redirect_to, $request, $user) {
    // Ensure $user is a valid WP_User object
    if (!is_wp_error($user) && isset($user->roles) && is_array($user->roles)) {
        if (in_array('tutor', $user->roles)) {
            return home_url('/tutors/lesson-management/my-availability/'); 
        } elseif (in_array('parent', $user->roles)) {
            return home_url('/parents/dashboard');
        } elseif (in_array('student', $user->roles)) {
            return home_url('/student/lesson-management/my-booked-class/');
        } 
    }
    // Default redirect to the homepage if no role matches
    return home_url('/wp-admin/');
}
add_filter('login_redirect', 'login_redirect_by_user', 10, 3);

function disable_admin_bar_for_specific_roles() {
    if (current_user_can('administrator')) {
        return true; // Show admin bar for administrators
    }
    
    return false; // Hide admin bar for all other roles (student, parent, teacher)
}
add_filter('show_admin_bar', 'disable_admin_bar_for_specific_roles');

function custom_logout_redirect() {
    wp_redirect(home_url()); // Redirects to homepage
    exit();
}
add_action('wp_logout', 'custom_logout_redirect');