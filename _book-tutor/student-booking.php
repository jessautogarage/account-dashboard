<?php
if(!defined('ABSPATH')) exit; // Exit if accessed directly

function student_booked_classes() {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to view your booked classes.</p>';
    }

    $current_user = wp_get_current_user();
    if (!in_array('student', $current_user->roles) && !in_array('administrator', $current_user->roles)) {
        return '<p>You do not have permission to view booked classes.</p>';
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
        "SELECT a.*, u.display_name AS tutor_name
         FROM $table_name a
         LEFT JOIN {$wpdb->prefix}users u ON a.user_id = u.ID
         WHERE a.student_id = %d AND a.date IN ($placeholders)",
        array_merge([$user_id], $dates)
    );

    $results = $wpdb->get_results($query, ARRAY_A);

    $time_slots = [];
    foreach ($results as $row) {
        $date = $row['date'];
        $time = date('H:i', strtotime($row['start_time']));
        $status = $row['status'];
        $tutor_name = $row['tutor_name'] ?? '';
        $tutor_id = $row['user_id'];

        if ($tutor_id && $tutor_name) {
            $label = "$tutor_name (ID: $tutor_id)";
            $link = get_author_posts_url($tutor_id);
            $label_html = "<a href='" . esc_url($link) . "' target='_blank'>$label</a>";
        } else {
            $label = $status;
            $label_html = esc_html($label);
        }

        $time_slots[$time][$date] = [
            'status' => $status,
            'label' => $label,
            'tutor_id' => $tutor_id ?? null
        ];
    }

    $all_times = [];
    $start = strtotime('00:00');
    $end = strtotime('23:30');
    while ($start <= $end) {
        $all_times[] = [
            'value' => date('H:i', $start),
            'label' => date('g:i A', $start)
        ];
        $start = strtotime('+30 minutes', $start);
    }

    $prev_start = date('Y-m-d', strtotime('-7 days', $start_timestamp));
    $next_start = date('Y-m-d', strtotime('+7 days', $start_timestamp));
    $month_label = date('F', $start_timestamp);
    $year_label = date('Y', $start_timestamp);

    $output = '<div class="availability-wrapper">';
    $output .= '<div class="slot-earning-info">';
    $output .= '<span><strong>Your Booked Classes</strong></span>';
    $output .= '<span>Total Booked: <strong>' . count(array_filter($results)) . '</strong></span>';
    $output .= '</div>';

    $output .= '<div class="calendar-header">';
    $output .= '<a href="?start=' . esc_attr($prev_start) . '" class="nav-link"><< Prev</a>';
    $output .= "<span class='calendar-month'>$month_label</span>";
    $output .= "<span class='calendar-year'>$year_label</span>";
    $output .= '<a href="?start=' . esc_attr($next_start) . '" class="nav-link">Next >></a>';
    $output .= '</div>';

    $output .= '<div class="availability-table-container">';
    $output .= '<table class="availability-table">';
    $output .= '<thead><tr><th>Time</th>';
    foreach ($dates as $date) {
        $output .= '<th><div class="date">' . date('m-d', strtotime($date)) . '</div>'
            . '<div class="day">' . date('D', strtotime($date)) . '</div></th>';
    }
    $output .= '</tr></thead><tbody>';

    foreach ($all_times as $time) {
        $output .= "<tr><td class='time'>{$time['label']}</td>";

        foreach ($dates as $date) {
            $slot = $time_slots[$time['value']][$date] ?? [
                'status' => 'Closed',
                'label' => 'Closed',
                'tutor_id' => null
            ];

            $class = strtolower($slot['status']);
            $tutor_id = $slot['tutor_id'];
            $label = esc_html($slot['label']);

            if ($tutor_id) {
                $profile_url = get_author_posts_url($tutor_id);
                $avatar = get_avatar($tutor_id, 24);
                $email = esc_html(get_userdata($tutor_id)->user_email);
                $tooltip = esc_attr("Name: $label\nEmail: $email\nDate: $date\nTime: {$time['label']}");

                $dropdown_html = "
                <a href='" . esc_url($profile_url) . "' target='_blank' title='$tooltip' style='text-decoration: none; display: block;'>
                    <div class='booked-cell-content'>
                        <div style='display:flex; align-items:center; gap:6px; justify-content:center;'>
                            $avatar <span>$label</span>
                        </div>
                        <div style='font-size:11px; color:#777;'>$email</div>
                        <select class='slot-dropdown' disabled style='margin-top:5px;'>
                            <option>$label</option>
                        </select>
                        <span class='checkmark' style='display:inline'>✔️</span>
                    </div>
                </a>";
            } else {
                $dropdown_html = "<select class='slot-dropdown' disabled><option>$label</option></select>";
            }

            $output .= "<td class='$class' data-date='$date' data-time='{$time['value']}'>$dropdown_html</td>";
        }

        $output .= '</tr>';
    }

    $output .= '</tbody></table></div></div>';

    // Reuse same CSS
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
        .calendar-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .availability-table { width: 100%; border-collapse: collapse; }
        .availability-table th,
        .availability-table td {
            border: 1px solid #eee;
            padding: 6px;
            text-align: center;
            font-size: 13px;
            vertical-align: middle;
        }
        .booked-cell-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .booked-cell-content div {
            margin-bottom: 3px;
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
        .checkmark {
            font-size: 14px;
            margin-left: 5px;
            color: #4caf50;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .show-check,
        .available .checkmark {
            opacity: 1 !important;
        }
    </style>';

    return $output;
}
add_shortcode('student_booked_classes', 'student_booked_classes');
