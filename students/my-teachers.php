<?php
if(!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function previous_teachers_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to view your previous teachers.</p>';
    }

    wp_enqueue_style(
        'previous-teacher-css',
        plugins_url('/css/previous-teacher.css', __FILE__), // We'll define this below
        array(),
        '1.0.0'
    );

    global $wpdb;
    $current_user_id = get_current_user_id();
    //var_dump($current_user_id); // Debugging line to check the current user ID

    // Get tutor IDs with past bookings by current user
    $tutor_ids = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT user_id 
        FROM {$wpdb->prefix}tutor_availability 
        WHERE student_id = %d AND is_booked = 1
    ", $current_user_id));

    if (empty($tutor_ids)) {
        return '<p>You have not booked any classes yet.</p>';
    }

    $args = [
        'include' => $tutor_ids,
        'role__in' => ['tutor'],
        'orderby' => 'display_name',
        'order' => 'ASC'
    ];
    $users = get_users($args);

    ob_start();
    echo '<div class="tutor-card-grid">';

    foreach ($users as $user) {
        $user_id = $user->ID;
        $name = esc_html($user->display_name);
        $avatar = get_user_meta($user_id, 'custom_profile_avatar', true) ?: get_avatar_url($user_id);
        $subjects = get_user_meta($user_id, 'subjects_handled', true);
        $subject_tags = '';

        if ($subjects) {
            $subject_list = explode(',', $subjects);
            foreach (array_slice($subject_list, 0, 2) as $subj) {
                $subject_tags .= '<span class="tag subject">' . esc_html(trim($subj)) . '</span>';
            }
        }

        echo '<div class="tutor-card">';
        echo '<div class="card-photo" style="background-image: url(' . esc_url($avatar) . ');"></div>';
        echo '<div class="card-tags">';
        echo $subject_tags ?: '<span class="tag subject">N/A</span>';
        echo '<span class="tag role">K-12 Teacher</span>';
        echo '</div>';
        echo '<div class="card-info">';
        echo '<h3 class="tutor-name">' . $name . '</h3>';
        // Define real links (customize these as needed)
        $schedule_url = site_url('/scheduled-classes/?tutor_id=' . $user_id); // or a custom route
        $rate_url = site_url('/rate-tutor/?tutor_id=' . $user_id);

        echo '<a href="'  . esc_url($schedule_url) . '" target="_blank" class="card-link">See<br>Scheduled Classes</a>';

        echo '<div class="card-rating">';
        echo '<img src="https://img.icons8.com/fluency-systems-filled/24/ccbf2f/star.png" class="star-icon" />';
        echo '<a href="' . esc_url($rate_url) . '" target="_blank" class="card-link">Rate</a>';
        echo '</div>';

        echo '</div>'; // .card-info
        echo '</div>'; // .tutor-card
    }

    echo '</div>';
    return ob_get_clean();
}
add_shortcode('previous_teachers', 'previous_teachers_shortcode');

