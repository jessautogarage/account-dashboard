<?php
if(!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


// function tutors_requirements() {
//     // Check if the user is logged in and has the 'tutor' role
//     if (is_user_logged_in() && current_user_can('tutor')) {
//         // User is logged in and has the 'tutor' role, proceed with loading the page
//         return true;
//     } else {
//         // User is not logged in or does not have the 'tutor' role, redirect to login page
//         wp_redirect(home_url('/login'));
//         exit;
//     }
// }
// add_action('template_redirect', 'tutors_requirements');

function tutors_requirements_shortcode(){
    
}