<?php
if(!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function tutor_booked_classes() {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to view booked classes.</p>';
    }

    $current_user = wp_get_current_user();
    if (!array_intersect(['tutor', 'administrator'], $current_user->roles)) {
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
        "SELECT a.*, u.display_name AS student_name
         FROM $table_name a
         LEFT JOIN {$wpdb->prefix}users u ON a.student_id = u.ID
         WHERE a.user_id = %d AND a.date IN ($placeholders)",
        array_merge([$user_id], $dates)
    );
    $results = $wpdb->get_results($query, ARRAY_A);

    $time_slots = [];
    foreach ($results as $row) {
        $date = $row['date'];
        $time = date('H:i', strtotime($row['start_time']));
        $status = $row['status'];
        $student_name = $row['student_name'] ?? '';
        $student_id = $row['student_id'];

        if ($status === 'Closed' && $student_id) {
            $status = 'Available'; // visually treated like "booked" for green color
        }


        if ($student_id && $student_name) {
            $label = "$student_name (ID: $student_id)";
            $link = get_author_posts_url($student_id); // Or use admin_url("user-edit.php?user_id=$student_id")
            $label_html = "<a href='" . esc_url($link) . "' target='_blank'>$label</a>";
        } else {
            $label = $status;
            $label_html = esc_html($label);
        }        
        $time_slots[$time][$date] = [
            'status' => $status,
            'label' => $label,
            'student_id' => $student_id ?? null
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
    $output .= '<span><strong>Booked Classes View</strong></span>';
    $output .= '<span>Total Booked: <strong>' . count(array_filter($results, fn($r) => $r['student_id'])) . '</strong></span>';
    $output .= '</div>';

    $output .= '<div class="calendar-header">';
    $output .= '<a href="?start=' . esc_attr($prev_start) . '" class="nav-link"><< Prev</a>';
    $output .= "<span class='calendar-month'>$month_label</span>";
    $output .= "<span class='calendar-year'>$year_label</span>";
    $output .= '<a href="?start=' . esc_attr($next_start) . '" class="nav-link">Next >></a>';
    $output .= '</div>';

    $output .= '<input type="text" id="booked-search" class="form-control w-25 mb-3" placeholder="Search student name or time..." style="min-width: 200px;">';
    $output .= '<div class="table-responsive">';
    $output .= '<table class="table table-bordered table-striped table-hover availability-table">';

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
                'student_id' => null
            ];
            
            $class = strtolower($slot['status']);
            $student_id = $slot['student_id'];
            $label = esc_html($slot['label']);
        
            // If booked, show profile with avatar + email + tooltip
            if ($student_id) {
                $profile_url = get_author_posts_url($student_id); // Or use admin_url(...)
                $avatar = get_avatar($student_id, 24);
                $email = esc_html(get_userdata($student_id)->user_email);
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
                $dropdown_html = "
                    <select class='slot-dropdown' disabled>
                        <option>" . esc_html($slot['label']) . "</option>
                    </select>";
            }
        
            $output .= "<td class='$class' data-date='$date' data-time='{$time['value']}'>
                $dropdown_html
            </td>";
        }
        
        $output .= '</tr>';
    }

    //$output .= '</tbody></table></div></div>';
    $output .= '</table></div></div>'; // close table and table-responsive


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
        .availability-table td {
            border: 1px solid #eee;
            padding: 6px;
            text-align: center;
            font-size: 13px;
            vertical-align: middle;
        }

        .availability-table thead th {
            background-color: #212529; /* dark header */
            color: #fff;
            vertical-align: middle;
            text-align: center;
            font-weight: bold;
            border: 1px solid #444;
        }

        .availability-table thead .date {
            display: block;
            font-weight: 600;
            font-size: 14px;
            color: #fff;
        }

        .availability-table thead .day {
            display: block;
            font-size: 12px;
            color: #999;
            margin-top: 2px;
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

        .show-check,
        .available .checkmark {
            opacity: 1 !important;
        }

        .booked {
            background-color: #e6f4ea !important;
            color: #2e7d32;
        }
        .booked .slot-dropdown {
            background-color: #e6f4ea;
            color: #2e7d32;
        }

    </style>';

    $output .= '<script>
    document.addEventListener("DOMContentLoaded", function () {
        const searchInput = document.getElementById("booked-search");
        searchInput.addEventListener("keyup", function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll(".availability-table tbody tr");

            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(filter) ? "" : "none";
            });
        });
    });
    </script>';


    return $output;
}
add_shortcode('tutor_booked_classes', 'tutor_booked_classes');
