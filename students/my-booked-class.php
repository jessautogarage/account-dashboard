<?php
if(!defined('ABSPATH')) exit; // Exit if accessed directly


function my_booked_class_shortcode() {
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

                // $dropdown_html = "
                // <a href='" . esc_url($profile_url) . "' target='_blank' title='$tooltip' style='text-decoration: none; display: block;'>
                //     <div class='booked-cell-content'>
                //         <div style='display:flex; align-items:center; gap:6px; justify-content:center;'>
                //             $avatar <span>$label</span>
                //         </div>
                //         <div style='font-size:11px; color:#777;'>$email</div>
                //         <select class='slot-dropdown' disabled style='margin-top:5px;'>
                //             <option>$label</option>
                //         </select>
                //         <span class='checkmark' style='display:inline'>✔️</span>
                //     </div>
                // </a>";

                $meet_link_html = '';
                if (!empty($row['meet_link'])) {
                    $meet_link = esc_url($row['meet_link']);
                    $meet_link_html = "<div><a href='$meet_link' target='_blank' class='btn btn-sm btn-success mt-2'>Join Meet</a></div>";
                }

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
                            $meet_link_html
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
        .availability-wrapper {
            font-family: "Segoe UI", sans-serif;
            padding: 20px;
        }

        .availability-table thead th {
            background-color: #3f4926;
            color: white;
            font-weight: bold;
            text-align: center;
            font-size: 13px;
        }

        .availability-table {
            width: 100%;
            border: 1px solid #dee2e6;
        }
        .availability-table th,
        .availability-table td {
            font-size: 13px;
            vertical-align: middle;
            padding: 6px;
            border: 1px solid #dee2e6;
            text-align: center;
        }
        .availability-table .time {
            font-weight: bold;
            background-color: #f1f1f1;
        }
        .availability-table .day {
            color: #888;
            font-size: 12px;
        }
        .closed {
            color: red !important;
        }
        .subject-math {
            color: #1a0dab; /* deep blue */
            font-weight: bold;
        }
        .subject-science {
            color: #ff9900; /* orange */
            font-weight: bold;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .calendar-header a {
            font-weight: bold;
            text-decoration: none;
            color: #3d4b1f;
        }
        .slot-earning-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 14px;
        }

    </style>';


    return $output;
}
add_shortcode('my_booked_class', 'my_booked_class_shortcode');