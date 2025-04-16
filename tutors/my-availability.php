<?php
if(!defined('ABSPATH')){
    exit;
}


function display_tutor_schedule_table() {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to view your schedule.</p>';
    }

    $current_user = wp_get_current_user();
    if (!array_intersect(['tutor', 'administrator'], $current_user->roles)) {
        return '<p>You do not have permission to view this schedule.</p>';
    }

     // Check online status from usermeta
     $online_status = get_user_meta($current_user->ID, 'tutor_online_status', true);
     if ($online_status === 'offline') {
         return '<p>Your schedule is not available while you are offline.</p>';
     }

    global $wpdb;
    $table_name = $wpdb->prefix . 'tutor_availability';
    $user_id = get_current_user_id();

    $start_date = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d');
    $start_timestamp = strtotime($start_date);

    $dates = [];
    for ($i = 0; $i < 7; $i++) {
        $dates[] = date('Y-m-d', strtotime("+$i days", $start_timestamp));
    }

    $placeholders = implode(',', array_fill(0, count($dates), '%s'));
    $query = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d AND date IN ($placeholders)",
        array_merge([$user_id], $dates)
    );
    $results = $wpdb->get_results($query, ARRAY_A);

    $time_slots = [];
    $opened_count = 0;
    $rate_per_slot = 3.5;

    foreach ($results as $row) {
        $date = $row['date'];
        $time = date('H:i', strtotime($row['start_time'])); // format time properly
        $status = $row['status'];
        $is_booked = $row['is_booked'] ?? 0;

        if ($is_booked == 1) {
            $status = 'Booked';
        } elseif ($status === 'Available') {
            $opened_count++;
        }

        $time_slots[$time][$date] = $status;

    }
    

    $all_times = [];
    $start = strtotime('00:00');
    $end = strtotime('23:30');
    while ($start <= $end) {
        $all_times[] = [
            'value' => date('H:i', $start), // used as lookup key
            'label' => date('g:i A', $start) // shown to the user
        ];        
        $start = strtotime('+30 minutes', $start);
    }

    $prev_start = date('Y-m-d', strtotime('-7 days', $start_timestamp));
    $next_start = date('Y-m-d', strtotime('+7 days', $start_timestamp));
    $month_label = date('F', $start_timestamp);
    $year_label = date('Y', $start_timestamp);
    $potential_earning = floatval($opened_count * $rate_per_slot);

    $output = '<div class="availability-wrapper">';
    $output .= '<div class="slot-earning-info">';
    $output .= '<span>Opened Slots: <strong>' . $opened_count . '</strong></span>';
    $output .= '<span>Potential Earning: <strong>$ ' . number_format($potential_earning, 2) . '</strong></span>';
    $output .= '<button class="submit-btn">SUBMIT</button>';
    $output .= '</div>';

    $output .= '<div class="calendar-header">';
    $output .= '<a href="?start=' . $prev_start . '" class="nav-link"><< Prev</a>';
    $output .= "<span class='calendar-month'>$month_label</span>";
    $output .= "<span class='calendar-year'>$year_label</span>";
    $output .= '<a href="?start=' . $next_start . '" class="nav-link">Next >></a>';
    $output .= '</div>';

    //$output .= '<input type="text" id="schedule-search" class="form-control mb-3" placeholder="Search time or status...">';
    $output .= '<div class="mb-3"><input type="text" id="schedule-search" class="form-control w-25" placeholder="Search time or status..." style="min-width: 200px;"></div>';

    $output .= '<div class="table-responsive">';
    $output .= '<table class="table table-bordered table-striped table-hover availability-table">';
    $output .= '<thead class="table-dark"><tr><th scope="col">Time</th>';

    
    foreach ($dates as $date) {
        $output .= '<th><div class="date">' . date('m-d', strtotime($date)) . '</div>'
                 . '<div class="day">' . date('D', strtotime($date)) . '</div></th>';
    }
    $output .= '</tr></thead><tbody>';

    foreach ($all_times as $time) {
        $output .= "<tr><td class='time'>{$time['label']}</td>";
        foreach ($dates as $date) {
            $status = $time_slots[$time['value']][$date] ?? 'Closed';
            $class = strtolower($status);
            $output .= "<td class='$class' data-date='$date' data-time='{$time['value']}'>";

            if ($status === 'Booked') {
                $output .= "<span class='booked-label'>Booked</span>";
            } else {
                $output .= "<select class='slot-dropdown'>
                    <option value='Available'" . ($status == 'Available' ? ' selected' : '') . ">Available</option>
                    <option value='Closed'" . ($status == 'Closed' ? ' selected' : '') . ">Closed</option>
                </select>
                <span class='checkmark' style='display:none'>✔️</span>";
            }

            $output .= "</td>";

        }
        $output .= '</tr>';
    }    

    //$output .= '</tbody></table></div></div>';
    $output .= '</table></div>'; // closes table and .table-responsive

    $output .= '<style>
        .availability-wrapper { font-family: Arial, sans-serif; }
        .slot-earning-info {
            background-color: #f9f9f9;
            padding: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .slot-earning-info strong { color: #e53935; }
        .submit-btn {
            background-color: #2e7d32;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .availability-table { width: 100%; border-collapse: collapse; }
        .availability-table th, .availability-table td {
            border: 1px solid #eee;
            padding: 6px;
            text-align: center;
            font-size: 13px;
        }
        .availability-table .time { font-weight: bold; background: #f5f5f5; }
        .availability-table .date { font-weight: bold; }
        .availability-table .day { color: #777; font-size: 12px; }
        .slot-dropdown {
            width: 100%;
            padding: 3px;
            border: none;
            border-radius: 4px;
        }
        .available { background-color: #e8f5e9; }
        .closed { background-color: #fbe9e7; }

        .available select {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .closed select {
            background-color: #ffebee;
            color: #c62828;
        }

        .checkmark {
            font-size: 14px;
            margin-left: 5px;
            color: #4caf50;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .show-check {
            opacity: 1 !important;
        }

        .booked {
            background-color: #e6f4ea;
            color: #2e7d32;
            font-weight: bold;
        }
        .booked-label {
            display: block;
            padding: 4px;
            font-size: 13px;
            color: #2e7d32;
        }


    </style>';

    $output .= '<script>
        document.addEventListener("DOMContentLoaded", function () {
                document.querySelectorAll(".slot-dropdown").forEach(function(dropdown) {
                    dropdown.addEventListener("change", function () {
                        const td = dropdown.closest("td");
                        const date = td.getAttribute("data-date");
                        const time = td.getAttribute("data-time");
                        const status = dropdown.value;
                        const checkmark = td.querySelector(".checkmark");

                        td.classList.remove("available", "closed");
                        td.classList.add(status.toLowerCase());

                        fetch("' . admin_url('admin-ajax.php') . '", {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: new URLSearchParams({
                                action: "toggle_availability",
                                date: date,
                                time: time,
                                status: status
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                checkmark.classList.add("show-check");
                                setTimeout(() => {
                                    checkmark.classList.remove("show-check");
                                }, 1200);
                            } else {
                                console.warn("Error:", data.data);
                            }
                        });
                    });
                });
            });
            </script>';
            $output .= '<script>
        document.addEventListener("DOMContentLoaded", function () {
            const searchInput = document.getElementById("schedule-search");
            searchInput.addEventListener("keyup", function () {
                const filter = this.value.toLowerCase();
                const rows = document.querySelectorAll(".availability-table tbody tr");

                rows.forEach(row => {
                    const timeCell = row.querySelector(".time").textContent.toLowerCase();
                    const rowText = row.textContent.toLowerCase();
                    row.style.display = rowText.includes(filter) || timeCell.includes(filter) ? "" : "none";
                });
            });

            // Dropdown logic remains here...
        });
        </script>';



    return $output;
}
add_shortcode('tutor_schedule_table', 'display_tutor_schedule_table');

// AJAX handler to toggle availability
add_action('wp_ajax_toggle_availability', 'handle_toggle_availability');

function handle_toggle_availability() {
    global $wpdb;

    $user_id = get_current_user_id();
    $date    = sanitize_text_field($_POST['date']);
    $time    = sanitize_text_field($_POST['time']);
    $status  = sanitize_text_field($_POST['status']);

    if (!$user_id || !$date || !$time || !$status) {
        wp_send_json_error('Missing required fields');
    }

    $table = $wpdb->prefix . 'tutor_availability';

    // Check if this slot already exists
    $existing = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND date = %s AND start_time = %s",
            $user_id, $date, $time
        )
    );

    // Calculate end_time (30 mins later)
    $start_ts = strtotime("$date $time");
    $end_time = date('H:i:s', strtotime('+30 minutes', $start_ts));

    if ($existing) {
        // Update existing slot
        $wpdb->update(
            $table,
            [
                'status'    => $status,
                'updated_at'=> current_time('mysql'),
                'end_time'  => $end_time
            ],
            ['id' => $existing->id]
        );
    } else {
        // Insert new slot
        $wpdb->insert(
            $table,
            [
                'user_id'    => $user_id,
                'date'       => $date,
                'start_time' => $time,
                'end_time'   => $end_time,
                'status'     => $status,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ]
        );
    }

    wp_send_json_success('Slot updated');
}



// Function to create the schedule management page in the admin area
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


// Function to display the schedule management page
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


// Enqueue scripts and styles for the calendar
function tutor_availability_picker() {
    $current_user = wp_get_current_user();

    // Only allow users with 'tutor' role
    if (!in_array('tutor', $current_user->roles)) {
        return '<p>You do not have permission to access this feature.</p>';
    }

    ob_start(); ?>
    
    <button id="show-calendar-btn">Add Availability</button>

    <div id="availability-form" style="margin-top: 20px;">
        <div class="availability-flex">
            <!-- Calendar on the left -->
            <div id="calendar" class="calendar-box"></div>

            <!-- Time selection on the right -->
            <div class="timeslot-box">
                <div id="selected-date-heading"></div>
                <div id="time-slots" style="margin: 20px 0;"></div>
                <button id="submit-availability" style="display:none;">Confirm Availability</button>
            </div>
        </div>
    </div>

    <!-- Styling -->
    <style>
        .availability-flex {
            display: flex;
            gap: 30px;
            align-items: flex-start;
            justify-content: start;
            flex-wrap: wrap;
        }

        #calendar {
            max-width: 100%;
            min-width: 300px;
            border: 1px solid #ddd;
            padding: 10px;
            background-color: #fff;
        }

        .timeslot-box {
            flex: 1;
            min-width: 300px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }

        .slot-btn {
            margin: 5px;
            padding: 10px 14px;
            background-color: #f5f5f5;
            color: #e91e63;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .slot-btn:hover {
            background-color: #e91e63;
            color: white;
        }

        .slot-btn.selected {
            background-color: #4caf50;
            color: white;
        }

        #submit-availability {
            margin-top: 20px;
            padding: 10px 16px;
            background-color: #2196F3;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('availability_picker', 'tutor_availability_picker');


// // AJAX to save multiple tutor slots
add_action('wp_ajax_save_multiple_tutor_slots', 'save_multiple_tutor_slots');
function save_multiple_tutor_slots() {
        $current_user = wp_get_current_user();
        if (!in_array('tutor', $current_user->roles)) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;

        $table = $wpdb->prefix . 'tutor_schedule';
        $tutor_id = $current_user->ID;
        $date = sanitize_text_field($_POST['date']);
        $time_slots = json_decode(stripslashes($_POST['time_slots']), true);

        if (!$date || empty($time_slots)) {
            wp_send_json_error('Missing data');
        }

        foreach ($time_slots as $time) {
            // Assume each slot is 30 mins long
            $start_time = $time;
            $end_time = date("H:i:s", strtotime($time) + 30 * 60);

            $wpdb->insert($table, [
                'tutor_id'   => $tutor_id,
                'date'       => $date,
                'start_time' => $start_time,
                'end_time'   => $end_time,
                'is_booked'  => 0
            ]);
        }

        wp_send_json_success('Availability saved!');
}

