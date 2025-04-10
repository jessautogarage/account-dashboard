<?php
if(!defined('ABSPATH')){
    exit();
}


function display_schedule_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'schedule';
    
    // Fetch schedule data
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY time_slot, day_of_week", ARRAY_A);

    // Define days of the week
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $time_slots = [];

    // Organize schedule data
    foreach ($results as $row) {
        $time_slots[$row['time_slot']][$row['day_of_week']] = $row['status'];
    }

    // Start table structure
    $output = '<div class="availability-table-container">';
    $output .= '<table class="availability-table">
        <thead>
            <tr>
                <th>Time</th>';
    
    foreach ($days as $day) {
        $output .= "<th>$day</th>";
    }

    $output .= '</tr></thead><tbody>';

    // Generate rows dynamically
    foreach ($time_slots as $time => $statuses) {
        $output .= "<tr><td class='time-slot'>$time</td>";
        foreach ($days as $day) {
            $status = isset($statuses[$day]) ? $statuses[$day] : 'Closed';
            $class = $status == 'Closed' ? 'closed' : 'open';
            $output .= "<td class='$class'><div class='status'>$status</div></td>";
        }
        $output .= '</tr>';
    }

    $output .= '</tbody></table></div>';
    return $output;
}
add_shortcode('schedule_table', 'display_schedule_table');



function schedule_admin_menu() {
    add_menu_page(
        'Manage Schedule', // Page title
        'Schedule Manager', // Menu title
        'manage_options', // Capability
        'schedule-manager', // Menu slug
        'schedule_manager_page', // Function to display content
        'dashicons-calendar-alt', // Icon
        20 // Position
    );
}
add_action('admin_menu', 'schedule_admin_menu');



function schedule_manager_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'schedule';

    if (isset($_POST['update_schedule'])) {
        foreach ($_POST['schedule'] as $id => $status) {
            $wpdb->update(
                $table_name,
                ['status' => sanitize_text_field($status)],
                ['id' => intval($id)]
            );
        }
        echo '<div class="updated"><p>Schedule updated successfully!</p></div>';
    }

    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY day_of_week, time_slot", ARRAY_A);
    
    echo '<div class="wrap"><h2>Manage Schedule</h2>';
    echo '<form method="post">';
    echo '<table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>Time Slot</th>
                    <th>Day of the Week</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($results as $row) {
        echo '<tr>
                <td>' . esc_html($row['time_slot']) . '</td>
                <td>' . esc_html($row['day_of_week']) . '</td>
                <td>
                    <select name="schedule[' . esc_attr($row['id']) . ']">
                        <option value="Open" ' . selected($row['status'], 'Open', false) . '>Open</option>
                        <option value="Closed" ' . selected($row['status'], 'Closed', false) . '>Closed</option>
                    </select>
                </td>
              </tr>';
    }

    echo '</tbody></table>';
    echo '<br><input type="submit" name="update_schedule" class="button-primary" value="Update Schedule">';
    echo '</form></div>';
}
