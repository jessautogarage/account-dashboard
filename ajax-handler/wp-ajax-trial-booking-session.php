<?php

// AJAX handler for fetching tutor details
add_action('wp_ajax_trial_booking_session', 'handle_trial_booking_session');
function handle_trial_booking_session() {
    global $wpdb;

    $slot_id = intval($_POST['slot_id']);
    $user = wp_get_current_user();

    if (!$slot_id || !in_array('student', $user->roles)) {
        wp_send_json_error(['message' => 'Unauthorized or invalid slot.']);
    }

    $table = $wpdb->prefix . 'tutor_availability';
    $slot = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $slot_id));

    if (!$slot || $slot->is_booked) {
        wp_send_json_error(['message' => 'Slot not found or already booked.']);
    }

    $wpdb->update($table, [
        'student_id' => $user->ID,
        'status'     => 'Booked',
        'booked_at'  => current_time('mysql'),
        'is_booked'  => 1
    ], ['id' => $slot_id]);

    wp_send_json_success(['message' => 'Trial booked successfully']);
}