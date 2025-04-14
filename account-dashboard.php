<?php
/*
 * Plugin Name:       Avocado Tutors Page Plugin
 * Description:       Manage pages, profile, tables to all of the users
 * Version:           1.0
 * Author:            Jesrel Agang
 * License:           GPL v2 or later
 */

 if ( ! defined( 'ABSPATH' ) ) {
    exit; 
}


class MyPluginLoader {
    private $plugin_path;

    public function __construct() {
        $this->plugin_path = plugin_dir_path(__FILE__);

        // Load all necessary files
        $this->load_all_files('profile/');
        $this->load_all_files('login/');
        $this->load_all_files('booking/');
        $this->load_all_files('tutors/');
        $this->load_all_files('students/');
        $this->load_all_files('classroom/');
    }

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

// Enqueue styles and scripts for the profile page
function enqueue_profile_style() {
    wp_enqueue_style( 'profile-css', plugins_url( 'assets/css/profile.css', __FILE__ ));
    wp_enqueue_style( 'availability-css', plugins_url( 'assets/css/availability.css', __FILE__ ));
}
add_action('wp_enqueue_scripts', 'enqueue_profile_style');


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

    add_role('payroll', 'Payroll',array(
        'read' => true, // Read-only permission
    ));
       
    add_role('accounting', 'Accounting', array(
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
            return home_url('/parents/lesson-management/my-childs-classes/');
        } elseif (in_array('student', $user->roles)) {
            return home_url('/student/lesson-management/my-booked-class/');
        } 
    }
    // Default redirect to the homepage if no role matches
    return home_url('/wp-admin');
}
add_filter('login_redirect', 'login_redirect_by_user', 10, 3);

// Redirect logged-in users from the homepage to their respective pages
function redirect_logged_in_users_from_home() {
    if (is_user_logged_in() && is_front_page()) {
        $user = wp_get_current_user();
        if (in_array('tutor', $user->roles)) {
            wp_redirect(home_url('/tutors/lesson-management/my-availability/'));
            exit;
        } elseif (in_array('parent', $user->roles)) {
            wp_redirect(home_url('/parents/lesson-management/my-childs-classes/'));
            exit;
        } elseif (in_array('student', $user->roles)) {
            wp_redirect(home_url('/student/lesson-management/my-booked-class/'));
            exit;
        } else {
            wp_redirect(home_url('/wp-admin'));
            exit;
        }
    }
}
add_action('template_redirect', 'redirect_logged_in_users_from_home');


// Redirect users without the 'administrator' role from accessing the admin dashboard
function disable_admin_bar_for_specific_roles() {
    if (current_user_can('administrator')) {
        return true; // Show admin bar for administrators
    }
    
    return false; // Hide admin bar for all other roles (student, parent, teacher)
}
add_filter('show_admin_bar', 'disable_admin_bar_for_specific_roles');

// Redirect users to the homepage after logout
function custom_logout_redirect() {
    wp_redirect(home_url()); // Redirects to homepage
    exit();
}
add_action('wp_logout', 'custom_logout_redirect');

// Load Bootstrap CSS and JS from CDN
function load_bootstrap_assets() {
    wp_enqueue_style(
        'bootstrap-css',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
        array(),
        '5.3.3'
    );

    wp_enqueue_script(
        'bootstrap-js',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
        array('jquery'),
        '5.3.3',
        true
    );
}
add_action('wp_enqueue_scripts', 'load_bootstrap_assets');

function enqueue_datatables_assets() {
    wp_enqueue_script('jquery'); // Needed for DataTables
    wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array('jquery'), null, true);
    wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');
}
add_action('wp_enqueue_scripts', 'enqueue_datatables_assets',10,1);
