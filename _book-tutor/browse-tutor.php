<?php
if (!defined('ABSPATH')) {
    exit;
}



function browse_tutors_shortcode() {
    wp_enqueue_style('student-booking-css', plugins_url('/css/student-booking.css', __FILE__));
    wp_enqueue_script('student-booking.js', plugins_url('/js/student-booking.js', __FILE__), array(), '1.0', true);
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to view available tutors.</p>';
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'tutor_availability';
    $users_table = $wpdb->prefix . 'users';
    $current_user_id = get_current_user_id();

    $start_date = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d');
    $start_timestamp = strtotime($start_date);

    $dates = [];
    for ($i = 0; $i < 7; $i++) {
        $dates[] = date('Y-m-d', strtotime("+$i days", $start_timestamp));
    }

    $placeholders = implode(',', array_fill(0, count($dates), '%s'));

    $usermeta_table = $wpdb->prefix . 'usermeta';

    $query = $wpdb->prepare(
        "SELECT a.*, u.display_name 
        FROM $table_name a 
        JOIN $users_table u ON a.user_id = u.ID 
        JOIN $usermeta_table m ON m.user_id = a.user_id 
        WHERE a.status = 'Available' 
        AND a.date IN ($placeholders)
        AND m.meta_key = 'tutor_online_status' 
        AND m.meta_value = 'online'
        ORDER BY a.date, a.start_time",
        $dates
    );


    $results = $wpdb->get_results($query, ARRAY_A);

    $slots = [];
    foreach ($results as $row) {
        $time = date('H:i', strtotime($row['start_time']));
        $slots[$time][$row['date']][] = $row;
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

    $output = '<div class="student-schedule-wrapper">';
    $output .= '<div class="calendar-header">';
    $output .= '<a href="?start=' . $prev_start . '" class="nav-link"><< Prev</a>';
    $output .= "<span class='calendar-month'>$month_label</span>";
    $output .= "<span class='calendar-year'>$year_label</span>";
    $output .= '<a href="?start=' . $next_start . '" class="nav-link">Next >></a>';
    $output .= '</div>';

    $output .= '
                <div id="bookingModal" class="modal" style="display:none;">
                    <div class="modal-overlay" onclick="closeBookingModal()"></div>
                    <div class="modal-content">
                        <button class="modal-close" onclick="closeBookingModal()">×</button>

                        <h2 class="modal-title">Book a Session</h2>
                        <p class="modal-subtext">
                            With <strong class="modal-tutor-name">Tutor</strong> on 
                            <span class="modal-slot-time">Date & Time</span>
                        </p>

                        <form id="checkoutForm">
                            <input type="hidden" name="slot_id" value="">
                            
                            <label for="subject_select">Select Subject</label>
                            <select id="subject_select" name="subject" required>
                                <option value="">Loading...</option>
                            </select>

                            <div class="modal-price" id="subject_price_display" style="margin-top:10px;"></div>

                            <button type="submit" class="modal-button">Book a Trial</button>
                        </form>
                    </div>
                </div>

                <style>
                .modal {
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                    background: rgba(0, 0, 0, 0.4); z-index: 9999; display: flex;
                    justify-content: center; align-items: center;
                }
                .modal-overlay {
                    position: absolute; width: 100%; height: 100%; top: 0; left: 0;
                }
                .modal-content {
                    background: #fff; padding: 24px 28px; border-radius: 10px;
                    position: relative; width: 400px; max-width: 90%; z-index: 10000;
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); font-family: sans-serif;
                }
                .modal-close {
                    position: absolute; top: 10px; right: 12px;
                    background: none; border: none; font-size: 24px;
                    color: #888; cursor: pointer;
                }
                .modal-title {
                    font-size: 20px; margin-bottom: 8px;
                }
                .modal-subtext {
                    font-size: 14px; color: #666; margin-bottom: 16px;
                }
                .modal-content label {
                    font-size: 14px; display: block; margin-bottom: 6px;
                }
                .modal-content select {
                    width: 100%; padding: 8px; font-size: 14px;
                    border: 1px solid #ccc; border-radius: 6px;
                }
                .modal-button {
                    background-color: #2e7d32; color: #fff;
                    padding: 10px 16px; border: none;
                    border-radius: 6px; font-size: 15px;
                    margin-top: 16px; cursor: pointer;
                    width: 100%;
                    transition: background 0.2s ease;
                }
                .modal-button:hover {
                    background-color: #1b5e20;
                }
                .modal-price {
                    font-size: 14px; color: #444;
                }
                </style>
                ';



    $output .= '<div class="availability-table-container">';
    $output .= '<table class="availability-table">';
    $output .= '<thead><tr><th>Time</th>';
    foreach ($dates as $date) {
        $output .= '<th><div class="date">' . date('m-d', strtotime($date)) . '</div><div class="day">' . date('D', strtotime($date)) . '</div></th>';
    }
    $output .= '</tr></thead><tbody>';

    foreach ($all_times as $time) {
        $output .= "<tr><td class='time'>{$time['label']}</td>";
        foreach ($dates as $date) {
            $cell = '';
            if (!empty($slots[$time['value']][$date])) {
                foreach ($slots[$time['value']][$date] as $row) {
                    $slot_id = esc_attr($row['id']);
                    $tutor_name = esc_html($row['display_name']);
                    $student_id = intval($row['student_id']);
                    $is_me = ($student_id === $current_user_id);
                    $class = $is_me ? 'booked-by-me' : '';
                    $label = $is_me ? "$tutor_name (You)" : $tutor_name;

                    $cell .= "<div class='available-tutor {$class}' 
                        data-slot-id='{$slot_id}' 
                        data-tutor='{$tutor_name}' 
                        data-date='{$date}' 
                        data-time='{$time['value']}'>
                        {$label}</div>";
                }
            } else {
                $cell = '<span class="closed">Closed</span>';
            }
            $output .= "<td>$cell</td>";
        }
        $output .= '</tr>';
    }

    $output .= '</tbody></table></div></div>';

    // CSS
    $output .= '<style>
        .availability-table td { text-align: center; vertical-align: middle; }
        .available-tutor {
            background: #e0f7fa;
            padding: 6px;
            margin: 4px 0;
            border-radius: 4px;
            font-weight: bold;
            color: #00796b;
            cursor: pointer;
            transition: background 0.2s ease;
            display: block;
        }
        .available-tutor:hover { background: #b2ebf2; }
        .booked-by-me {
            background-color: #c8e6c9 !important;
            color: #2e7d32 !important;
        }
        .calendar-header {
            display: flex; justify-content: space-between; margin-bottom: 10px;
        }
        .availability-table {
            width: 100%; border-collapse: collapse; text-align: center;
        }
        .availability-table th, .availability-table td {
            border: 1px solid #eee; padding: 6px; font-size: 13px;
        }
        .availability-table .time { font-weight: bold; background: #f5f5f5; }
        .availability-table .date { font-weight: bold; }
        .availability-table .day { color: #777; font-size: 12px; }
        .nav-link { text-decoration: none; color: #2e7d32; font-weight: bold; }
    </style>';

    // JavaScript
    $output .= '<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".available-tutor").forEach(function (el) {
                el.addEventListener("click", function () {
                const slotId = el.getAttribute("data-slot-id");
                const tutorName = el.getAttribute("data-tutor");
                const date = el.getAttribute("data-date");
                const time = el.getAttribute("data-time");

                // Open modal and inject data
                openBookingModal({ slotId, tutorName, date, time });
            });

        });
    });
    </script>';

    $output .= '<script>
                function closeBookingModal() {
                    const modal = document.getElementById("bookingModal");
                    if (modal) modal.style.display = "none";
                }

                function openBookingModal({ slotId, tutorName, date, time }) {
                    const modal = document.getElementById("bookingModal");
                    if (!modal) return;

                    modal.querySelector(".modal-tutor-name").textContent = tutorName;
                    modal.querySelector(".modal-slot-time").textContent = `${date} at ${time}`;
                    modal.querySelector("input[name=\'slot_id\']").value = slotId;

                    fetchTutorDetails(slotId);
                    modal.style.display = "flex";
                }

                function fetchTutorDetails(slotId) {
                    const select = document.getElementById("subject_select");
                    const priceDisplay = document.getElementById("subject_price_display");

                    select.innerHTML = "<option>Loading...</option>";
                    priceDisplay.textContent = "";

                    fetch("' . admin_url('admin-ajax.php') . '", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: new URLSearchParams({
                            action: "get_tutor_details",
                            slot_id: slotId
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) {
                            select.innerHTML = "<option disabled>Error loading subjects</option>";
                            return;
                        }

                        const subjects = data.data;
                        select.innerHTML = "<option value=\'\'>Select a subject</option>";

                        subjects.forEach(subj => {
                            const opt = document.createElement("option");
                            opt.value = subj.subject;
                            opt.textContent = `${subj.subject} - $${subj.price}`;
                            opt.setAttribute("data-price", subj.price);
                            select.appendChild(opt);
                        });

                        select.addEventListener("change", function () {
                            const price = this.selectedOptions[0].getAttribute("data-price");
                            priceDisplay.textContent = price ? `Price: $${price}` : "";
                        });
                    })
                    .catch(error => {
                        console.error("Error fetching tutor details:", error);
                        select.innerHTML = "<option disabled>Failed to load</option>";
                    });
                }
                </script>';

    $output .= '<script>
                function closeBookingModal() {
                    const modal = document.getElementById("bookingModal");
                    if (modal) modal.style.display = "none";
                }
                
                function openBookingModal({ slotId, tutorName, date, time }) {
                    const modal = document.getElementById("bookingModal");
                    if (!modal) return;
                
                    modal.querySelector(".modal-tutor-name").textContent = tutorName;
                    modal.querySelector(".modal-slot-time").textContent = `${date} at ${time}`;
                    modal.querySelector("input[name=\'slot_id\']").value = slotId;
                
                    fetchTutorDetails(slotId);
                    modal.style.display = "flex";
                }
                
                function fetchTutorDetails(slotId) {
                    const select = document.getElementById("subject_select");
                    const priceDisplay = document.getElementById("subject_price_display");
                
                    select.innerHTML = "<option>Loading...</option>";
                    priceDisplay.textContent = "";
                
                    fetch("' . admin_url('admin-ajax.php') . '", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: new URLSearchParams({
                            action: "get_tutor_details",
                            slot_id: slotId
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (!data.success) {
                            select.innerHTML = "<option disabled>Error loading subjects</option>";
                            return;
                        }
                
                        const subjects = data.data;
                        select.innerHTML = "<option value=\'\'>Select a subject</option>";
                
                        subjects.forEach(subj => {
                            const opt = document.createElement("option");
                            opt.value = subj.subject;
                            opt.textContent = `${subj.subject} - $${subj.price}`;
                            opt.setAttribute("data-price", subj.price);
                            select.appendChild(opt);
                        });
                
                        select.addEventListener("change", function () {
                            const price = this.selectedOptions[0].getAttribute("data-price");
                            priceDisplay.textContent = price ? `Price: $${price}` : "";
                        });
                    })
                    .catch(error => {
                        console.error("Error fetching tutor details:", error);
                        select.innerHTML = "<option disabled>Failed to load</option>";
                    });
                }
            </script>';


        $output .= "<script>
            document.getElementById('checkoutForm').addEventListener('submit', function (e) {
                e.preventDefault();
            
                const slotId = this.querySelector(\"input[name='slot_id']\").value;
            
                if (!slotId) {
                    alert('Slot ID missing.');
                    return;
                }
            
                fetch('" . admin_url('admin-ajax.php') . "', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'trial_booking_session',
                        slot_id: slotId
                    })
                })
                .then(async res => {
                    const text = await res.text();
                    try {
                        const data = JSON.parse(text);
                        console.log('Booking result:', data);
            
                        if (data.success) {
                            alert('Trial session booked successfully!');
                            closeBookingModal();
                            window.location.reload();
                        } else {
                            alert(data.data.message || 'Booking failed.');
                        }
                    } catch (err) {
                        console.error('Invalid JSON from server:', text);
                        alert('Unexpected server response. See console.');
                    }
                })
                .catch(err => {
                    console.error('Fetch failed:', err);
                    alert('Error booking trial.');
                });
            });
            </script>";
            
    return $output;
}
add_shortcode('browse_tutors', 'browse_tutors_shortcode');

add_action('wp_ajax_trial_booking_session', 'handle_trial_booking_session');
add_action('wp_ajax_nopriv_trial_booking_session', 'handle_trial_booking_session');

function handle_trial_booking_session() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to book.']);
    }

    global $wpdb;

    $slot_id = isset($_POST['slot_id']) ? intval($_POST['slot_id']) : 0;
    $user_id = get_current_user_id();

    if (!$slot_id) {
        wp_send_json_error(['message' => 'Invalid slot ID.']);
    }

    $table = $wpdb->prefix . 'tutor_availability';

    // Check if the slot is still available
    $slot = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d AND is_booked = 0 AND status = 'Available'",
        $slot_id
    ));

    if (!$slot) {
        wp_send_json_error(['message' => 'This slot is no longer available.']);
    }

    // Update booking
    $updated = $wpdb->update(
        $table,
        [
            'student_id' => $user_id,
            'is_booked'  => 1,
            'status'     => 'Booked',
            'booked_at'  => current_time('mysql')
        ],
        ['id' => $slot_id]
    );

    if ($updated !== false) {
        wp_send_json_success(['message' => 'Trial session successfully booked!']);
    } else {
        wp_send_json_error(['message' => 'Failed to book the session.']);
    }
}
