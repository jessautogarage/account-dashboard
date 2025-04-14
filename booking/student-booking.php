<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function student_booking_table() {
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

                            <button type="submit" class="modal-button">Book & Pay</button>
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
                
                // SUBMIT PAYMENT + START STRIPE CHECKOUT
                document.getElementById("checkoutForm").addEventListener("submit", function (e) {
                    e.preventDefault();
                
                    const slotId = this.querySelector("input[name=\'slot_id\']").value;
                    const subject = this.querySelector("select[name=\'subject\']").value;
                
                    if (!slotId || !subject) {
                        alert("Please select a subject.");
                        return;
                    }
                
                    fetch("' . admin_url('admin-ajax.php') . '", {
                        method: "POST",
                        headers: { "Content-Type": "application/x-www-form-urlencoded" },
                        body: new URLSearchParams({
                            action: "start_checkout_session",
                            slot_id: slotId,
                            subject: subject
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.data.checkout_url) {
                            window.location.href = data.data.checkout_url;
                        } else {
                            alert("Payment initiation failed.");
                        }
                    })
                    .catch(err => {
                        console.error("Stripe session error:", err);
                        alert("Error starting payment.");
                    });
                });
            </script>';
                



    return $output;
}
add_shortcode('student_booking', 'student_booking_table');



// Handle AJAX request to get tutor details
add_action('wp_ajax_get_tutor_details', 'get_tutor_details');
add_action('wp_ajax_nopriv_get_tutor_details', 'get_tutor_details');
function get_tutor_details() {
    if (!isset($_POST['slot_id'])) {
        wp_send_json_error("Missing slot ID.");
    }

    global $wpdb;
    $slot_id = intval($_POST['slot_id']);

    $slot = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tutor_availability WHERE id = %d", $slot_id
    ));

    if (!$slot) {
        wp_send_json_error("Slot not found.");
    }

    $tutor_id = intval($slot->user_id);

    $user = get_userdata($tutor_id);
    if (!$user || !in_array('tutor', (array) $user->roles)) {
        wp_send_json_error("Not a valid tutor.");
    }

    $subjects = $wpdb->get_results($wpdb->prepare(
        "SELECT subject, price FROM {$wpdb->prefix}tutor_subjects WHERE user_id = %d", $tutor_id
    ), ARRAY_A);

    if (empty($subjects)) {
        wp_send_json_error("No subjects found.");
    }

    wp_send_json_success($subjects);
}
// Handle AJAX request to book a slot




// Handle AJAX booking
add_action('wp_ajax_book_tutor_slot', 'handle_book_tutor_slot');
function handle_book_tutor_slot() {
    if (!is_user_logged_in()) {
        wp_send_json_error("You must be logged in.");
    }

    global $wpdb;
    $table = $wpdb->prefix . 'tutor_availability';
    $student_id = get_current_user_id();
    $slot_id = intval($_POST['slot_id']);
    $do = sanitize_text_field($_POST['do'] ?? 'book');

    $slot = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $slot_id));
    if (!$slot) {
        wp_send_json_error("Slot not found.");
    }

    if ($do === 'cancel') {
        if ((int)$slot->student_id !== $student_id) {
            wp_send_json_error("You can only cancel your own bookings.");
        }

        $wpdb->update($table, [
            'is_booked' => 0,
            'booked_at' => null,
            'student_id' => null
        ], ['id' => $slot_id]);

        wp_send_json_success("Booking canceled.");
    }

    if ($slot->is_booked) {
        wp_send_json_error("Slot already booked.");
    }

    // Prevent double booking at the same time
    $conflict = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE date = %s AND start_time = %s AND student_id = %d AND is_booked = 1",
        $slot->date, $slot->start_time, $student_id
    ));

    if ($conflict > 0) {
        wp_send_json_error("You already booked a class at this time.");
    }

    $updated = $wpdb->update(
        $table,
        [
            'is_booked' => 1,
            'booked_at' => current_time('mysql'),
            'student_id' => $student_id
        ],
        ['id' => $slot_id],
        ['%d', '%s', '%d'],
        ['%d']
    );

    if ($updated === false) {
        wp_send_json_error("Database error.");
    }

    wp_send_json_success("Booked successfully.");
}



add_action('wp_ajax_cancel_tutor_slot', 'cancel_tutor_slot');
function cancel_tutor_slot() {
    check_ajax_referer('book_slot_nonce', 'nonce');

    global $wpdb;
    $user_id = get_current_user_id();
    $schedule_id = intval($_POST['schedule_id']);
    $table = $wpdb->prefix . 'tutor_availability';

    $slot = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE id = %d AND student_id = %d",
        $schedule_id, $user_id
    ));

    if (!$slot) {
        wp_send_json_error(['message' => 'Booking not found.']);
    }

    $result = $wpdb->update($table, [
        'status'     => 'Available',
        'student_id' => null,
        'booked_at'  => null
    ], ['id' => $schedule_id]);

    if ($result === false) {
        wp_send_json_error(['message' => 'Failed to cancel booking.']);
    }

    wp_send_json_success(['message' => 'Booking cancelled.']);
}



// Load Stripe SDK via Composer (recommended)
require_once __DIR__ . '/vendor/autoload.php';

// ✅ Stripe Checkout Session Creation
add_action('wp_ajax_start_checkout_session', 'start_checkout_session');
add_action('wp_ajax_nopriv_start_checkout_session', 'start_checkout_session');

function start_checkout_session() {
    if (empty($_POST['slot_id']) || empty($_POST['subject'])) {
        wp_send_json_error("Missing data.");
    }

    global $wpdb;
    $slot_id = intval($_POST['slot_id']);
    $subject = sanitize_text_field($_POST['subject']);
    $user_id = get_current_user_id();

    $slot = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tutor_availability WHERE id = %d", $slot_id
    ));
    if (!$slot) wp_send_json_error("Invalid slot.");
    $tutor_id = $slot->user_id;

    $subject_row = $wpdb->get_row($wpdb->prepare(
        "SELECT price FROM {$wpdb->prefix}tutor_subjects WHERE user_id = %d AND subject = %s",
        $tutor_id, $subject
    ));
    if (!$subject_row) wp_send_json_error("Invalid subject.");

    $price = floatval($subject_row->price);
    \Stripe\Stripe::setApiKey('sk_test_51RAkICRPNdbWW7KwsJZxvQynxeOMDRJCM3MvlNvqqiUoh5UlWyI5Dd5otigUCifXfplFQeyD0SnMpxpuSFnLWOvq00TMlQ5pmT');

    try {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => ['name' => $subject],
                    'unit_amount' => $price * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => home_url('/booking-success?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => home_url('/booking-cancelled'),
            'metadata' => [
                'slot_id' => $slot_id,
                'user_id' => $user_id,
                'subject' => $subject,
            ]
        ]);
        wp_send_json_success(['checkout_url' => $session->url]);
    } catch (Exception $e) {
        wp_send_json_error("Stripe error: " . $e->getMessage());
    }
}



add_action('template_redirect', 'handle_booking_success');

function handle_booking_success() {
    if (!is_page('booking-success') || !isset($_GET['session_id'])) return;

    $session_id = sanitize_text_field($_GET['session_id']);

    // Load Stripe
    \Stripe\Stripe::setApiKey('sk_test_51RAkICRPNdbWW7KwsJZxvQynxeOMDRJCM3MvlNvqqiUoh5UlWyI5Dd5otigUCifXfplFQeyD0SnMpxpuSFnLWOvq00TMlQ5pmT');

    try {
        $session = \Stripe\Checkout\Session::retrieve($session_id);
        $meta = $session->metadata;

        $user_id   = intval($meta['user_id']);
        $slot_id   = intval($meta['slot_id']);
        $subject   = sanitize_text_field($meta['subject']);
        $price     = floatval($session->amount_total / 100);
        $tutor_cut = $price * 0.80;

        global $wpdb;

        // ✅ Update tutor availability
        $updated = $wpdb->update(
            "{$wpdb->prefix}tutor_availability",
            [
                'status'     => 'Booked',
                'is_booked'  => 1,
                'booked_at'  => current_time('mysql'), // 🔧 fixed key
                'student_id' => $user_id,
            ],
            ['id' => $slot_id],
            ['%s', '%d', '%s', '%d'],
            ['%d']
        );        
        
        

        

        if ($updated === false) {
            error_log("❌ Failed to mark slot {$slot_id} as booked.");
            wp_redirect(home_url('/booking-failed'));
            exit;
        }

        // ✅ Save earnings
        $tutor_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}tutor_availability WHERE id = %d",
            $slot_id
        ));

        $wpdb->insert("{$wpdb->prefix}tutor_earnings", [
            'tutor_id'   => $tutor_id,
            'student_id' => $user_id,
            'subject'    => $subject,
            'price'      => $price,
            'earnings'   => $tutor_cut,
            'booked_at'  => current_time('mysql')
        ]);

        update_post_meta($slot_id, '_booked_subject', $subject);

        // ✅ Save success data in user meta
        update_user_meta($user_id, '_last_booking_success', [
            'subject'   => $subject,
            'price'     => $price,
            'tutor_id'  => $tutor_id,
            'time'      => current_time('mysql')
        ]);

        error_log("✅ Booking meta saved for user {$user_id}");
        $check = get_user_meta($user_id, '_last_booking_success', true);
        error_log("✅ Meta contents: " . print_r($check, true));


        // ✅ Redirect to final confirmation page
        wp_redirect(home_url('/booking-success-final'));
        exit;

    } catch (Exception $e) {
        error_log("❌ Stripe exception: " . $e->getMessage());
        wp_redirect(home_url('/booking-failed'));
        exit;
    }
}



add_shortcode('booking_success_page', 'booking_success_page_content');

function booking_success_page_content() {
    // $user_id = get_current_user_id();
    // $data = get_transient("booking_success_{$user_id}");

    // if (!$data) {
    //     return '<p>No recent bookings found.</p>';
    // }

    $user_id = get_current_user_id();

    error_log("LOOKING FOR TRANSIENT booking_success_{$user_id}");

    $data = get_transient("booking_success_{$user_id}");

    if (!$data) {
        return '<p>No recent bookings found. (Debug ID: ' . $user_id . ')</p>';
    }

    $tutor_name = get_userdata($data['tutor_id'])->display_name ?? 'Tutor';

    ob_start();
    ?>
    <div class="booking-success-box">
        <h2>🎉 Booking Confirmed!</h2>
        <p>You successfully booked a session with <strong><?php echo esc_html($tutor_name); ?></strong>.</p>
        <ul>
            <li><strong>Subject:</strong> <?php echo esc_html($data['subject']); ?></li>
            <li><strong>Amount Paid:</strong> $<?php echo number_format($data['price'], 2); ?></li>
            <li><strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($data['time'])); ?></li>
        </ul>
        <a href="<?php echo esc_url(home_url('/my-booked-classes')); ?>" class="booking-btn">Go to My Classes</a>
    </div>
    <style>
        .booking-success-box {
            background: #e8f5e9;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            margin: 40px auto;
            text-align: center;
            font-family: sans-serif;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .booking-success-box h2 { color: #2e7d32; }
        .booking-success-box ul {
            list-style: none;
            padding: 0;
            margin: 20px 0;
            text-align: left;
        }
        .booking-success-box li { margin-bottom: 8px; font-size: 15px; }
        .booking-btn {
            display: inline-block; padding: 10px 20px;
            background: #2e7d32; color: white; text-decoration: none;
            border-radius: 6px; margin-top: 20px; transition: background 0.2s;
        }
        .booking-btn:hover { background: #1b5e20; }
    </style>
    <?php
    return ob_get_clean();
}

