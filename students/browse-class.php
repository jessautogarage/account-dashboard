<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

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

    // Filter UI: Collect available tutor names
    $tutor_names = array_unique(array_column($results, 'display_name'));
    sort($tutor_names);

    // Tutor filter dropdown form
    $output = '<form method="GET" id="filter-form" class="filter-form" style="margin-bottom: 20px;">';
    $output .= '<label for="filter_tutor"><strong>Filter by Tutor:</strong></label> ';
    $output .= '<select name="filter_tutor" id="filter_tutor" onchange="this.form.submit()">';
    $output .= '<option value="">All Tutors</option>';

    $selected_filter = isset($_GET['filter_tutor']) ? $_GET['filter_tutor'] : '';
    foreach ($tutor_names as $name) {
        $selected = ($selected_filter === $name) ? 'selected' : '';
        $output .= '<option value="' . esc_attr($name) . '" ' . $selected . '>' . esc_html($name) . '</option>';
    }
    $output .= '</select> ';
    $output .= '<input type="hidden" name="start" value="' . esc_attr($start_date) . '" />';
    $output .= '</form>';

    // Apply tutor filter to $results
    if ($selected_filter) {
        $results = array_filter($results, function ($row) use ($selected_filter) {
            return $row['display_name'] === $selected_filter;
        });
    }


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
            'label' => date('h:i A', $start)
        ];
        $start = strtotime('+30 minutes', $start);
    }


    $prev_start = date('Y-m-d', strtotime('-7 days', $start_timestamp));
    $next_start = date('Y-m-d', strtotime('+7 days', $start_timestamp));
    $month_label = date('F', $start_timestamp);
    $year_label = date('Y', $start_timestamp);

    $output = '<div class="availability-wrapper">';
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

                            <label for="duration_select" style="margin-top: 10px;">Select Duration</label>
                            <select id="duration_select" name="duration" required>
                                <option value="">Choose Duration</option>
                                <option value="0.5 Hour" data-rate="6">0.5 Hour</option>
                                <option value="1.0 Hour" data-rate="12">1 Hour</option>
                                <option value="1.5 Hours" data-rate="18">1.5 Hours</option>
                                <option value="2.0 Hours" data-rate="24">2 Hours</option>
                                <option value="2.5 Hours" data-rate="30">2.5 Hours</option>
                                <option value="3.0 Hours" data-rate="36">3 Hours</option>
                                <option value="3.5 Hours" data-rate="42">3.5 Hours</option>
                                <option value="4.0 Hours" data-rate="48">4 Hours</option>
                                <option value="4.5 Hours" data-rate="54">4.5 Hours</option>
                                <option value="5.0 Hours" data-rate="60">5 Hours</option>
                                <option value="6.0 Hours" data-rate="72">6 Hours</option>
                                <option value="7.0 Hours" data-rate="84">7 Hours</option>
                                <option value="8.0 Hours" data-rate="96">8 Hours</option>
                                <option value="9.0 Hours" data-rate="108">9 Hours</option>
                                <option value="10.0 Hours" data-rate="120">10 Hours</option>
                            </select>


                            <div class="modal-price" id="subject_price_display" style="margin-top:10px;"></div>

                            <button type="submit" class="modal-button">Book a Trial</button>
                            <button type="button" class="modal-button paid-booking-button" onclick="startPaidBooking()">Book a Tutor (Paid)</button>

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

                .filter-form {
                    font-family: "Segoe UI", sans-serif;
                    margin-bottom: 20px;
                }
                .filter-form select {
                    padding: 6px 10px;
                    border-radius: 5px;
                    border: 1px solid #ccc;
                    font-size: 14px;
                }

                </style>
                ';



    $output .= '<div class="table-responsive mt-4">';
    $output .= '<table class="table table-bordered text-center align-middle availability-table">';
                

                
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
                $cell = '<select class="form-select form-select-sm" disabled><option selected>Closed</option></select>';
            }
            $output .= "<td>$cell</td>";
        }
        $output .= '</tr>';
    }

    $output .= '</tbody></table></div></div>';

    // CSS
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
                document.getElementById("duration_select").addEventListener("change", function () {
                    const selectedOption = this.options[this.selectedIndex];
                    const rate = selectedOption.getAttribute("data-rate");
                    const display = document.getElementById("subject_price_display");

                    if (rate) {
                        display.textContent = `Price: $${rate} total`;
                    } else {
                        display.textContent = "";
                    }
                });

                function closeBookingModal() {
                    const modal = document.getElementById("bookingModal");
                    if (modal) modal.style.display = "none";
                }

                function formatTo12Hour(timeStr) {
                    const [hour, minute] = timeStr.split(":").map(Number);
                    const ampm = hour >= 12 ? "PM" : "AM";
                    const hour12 = hour % 12 || 12;
                    return `${String(hour12).padStart(2, "0")}:${minute.toString().padStart(2, "0")} ${ampm}`;
                }


                function openBookingModal({ slotId, tutorName, date, time }) {
                    const modal = document.getElementById("bookingModal");
                    if (!modal) return;

                    modal.querySelector(".modal-tutor-name").textContent = tutorName;
                    const formattedTime = formatTo12Hour(time);
                    modal.querySelector(".modal-slot-time").textContent = `${date} at ${formattedTime}`;
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
                            opt.textContent = `${subj.subject}`;
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
            function startPaidBooking() {
            const durationSelect = document.getElementById("duration_select");
            const selectedDuration = durationSelect.value;

            if (!selectedDuration) {
                alert("Please select a duration before booking.");
                return;
            }
        
                const paymentLinks = {
                    "0.5 Hour": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-9cc5b402bb714a1fa69c5db6d803e3e3177692e39f8a41c9947efa878aa969aebf92311afa734a2e9a92fdbe39fd449b?locale=EN_US",
                    "1.0 Hour": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-649faf051ba644eaa21b8d48d723b77d8710ae01205243e695aef1863b253741ff188326a8c14f688d31043cfb2f5980?locale=EN_US",
                    "1.5 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-be1957e72bf2460b94fd183285928b3409e018e30a354b389a5a84d71a4d1ccb88fe8ba8af284c2c87cb28f6cdb9de69?locale=EN_US",
                    "2.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-85463c268a7c4f5d936c567924ab30eaf46c7494ea0f440a8ba451d7c3dd2ee3aacdd5e1e0664c5183cfdff780925bb6?locale=EN_US",
                    "2.5 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-eb04e9933e204a4998d7eecce1021fb8c7baea09c16a4906892a381517e4078d0c1df12f67fa46b78ceaf64bf386790e?locale=EN_US",
                    "3.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-f380a6f4313e42e3b411396f7c08f400acd100e4b82d48b08f594b8d98e56463845cf0b66d4f4ead85dbc897713a1b5d?locale=EN_US",
                    "3.5 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-8b375fd2e282423db5321ccb849c953b7faf71d052b44a969ac87b48957d042e4d1763be996947189fc63e52b6893aec?locale=EN_US",
                    "4.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-069dfff2fd6e47e5bd27105991c8fdc05d969a59333a4af6898ed81d8a08ff6158e13527bb884e768a39fda519a7eb96?locale=EN_US",
                    "4.5 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-69e7d5b36c9045f8ba7458e8814bc3c3b4f3b47755f340c8b67bb15bd7494a1f89080d36f21648459a6d8943a09201d4?locale=EN_US",
                    "5.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-6297b4b01c7d460f858f7e1e0949231b1e39729fd4924604a2a8b4a5655f2367cc2a216eef524eedb34bc456dde3b597?locale=EN_US",
                    "6.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-ed0bd803479f4fd5bb66d31365bfe08c6c33987df58b437181a81bc61713fc31670b9102b7da4546ada9508da52a0c80?locale=EN_US",
                    "7.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-8010a321235e49119637095daa76450a420903c090df48ca84715ee092bc71c889dfc8130ea04bca84acb8d749993401?locale=EN_US",
                    "8.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-d32b0a0cf2a844dba7e432f3920dd171ef5f1e1c9fb94c9bb7a3020b83cedf00c86409c3649947aebdff67393897ec42?locale=EN_US",
                    "9.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-86402a0c666a4647a574e11c8ad7917ba82dd2d8c75845e9a37bd8400e4db237b18f09a8513147db84e19e679805dd26?locale=EN_US",
                    "10.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-93566e762fd446ed9c63c24c10b4a9fe5ca02367849a4b73885897dc9b4a1d9bc102c593344046728649fd17ead9304a?locale=EN_US",
                    "20.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-360fa4542d46486e8fc510f07057b729f8f7dd359dda4001ad6b117ed2f003f04d4244d52d5e40e7b44cea3a2b36a4f7?locale=EN_US",
                    "30.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-92176ddadeb24e74b153335d22839c3cd34266a7d4764679ab20722709c3aa3b37adfa6bf1134bb886a5969cf57473b2?locale=EN_US",
                    "40.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-3e82d6704cf541c7961e4925a8db82691aa658b2c267474da7d2e5f4ae744f3bded35bc980e04349aa723ff95fd99fff?locale=EN_US",
                    "50.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-a57660385e92439ead3dc4dea34580e08b0a3c86505e49c0ac6b883be2209ee95d9b6ab0ec154fe2bd7aa62a72e4aa90?locale=EN_US",
                    "60.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-638481670baf41688e5e900ec0e32f419cd4ffbc1f3d4b4d83b45f8b65ac6dc36a051057ab4c4433ab44dd1cc0cac62a?locale=EN_US",
                    "70.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-9f061667d98d43928f9f94e399cd7dbf2a4aa5d69cd54e188d1b6f4080c3e0fa31e36cbb7e5b492e866dd7e33204a778?locale=EN_US",
                    "80.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-99bd01a3aac343a1b43ea46a9d9266a7512ea7ffb2984fb18fd35a65a2db68144afc20bc40954a57aacf8bea46be42a0?locale=EN_US",
                    "90.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-44acff2650a34408a3ce75b2c4883c8cca4e7a9bf6c4444db5049ed15898f75965cf07d0e98f438aa7d6bc615b6a87d9?locale=EN_US",
                    "100.0 Hours": "https://connect.intuit.com/portal/app/CommerceNetwork/view/scs-v1-eed0cb5dc28e454c966b766cdec4feb8914e323d66d44d4dbc1950fd3695ad9c554d5ecf94e24cd2a3c558f37bdb1d4a?locale=EN_US"
                };

        
                        const link = paymentLinks[selectedDuration];

                if (link) {
                    window.location.href = link;
                } else {
                    alert("No payment link found for the selected duration.");
                }
            }
        </script>';

        $output .= '<script>
        document.getElementById("checkoutForm").addEventListener("submit", function (e) {
            e.preventDefault();

            const slotId = this.querySelector("input[name=\'slot_id\']").value;

            if (!slotId) {
                alert("Slot ID missing.");
                return;
            }

            fetch("' . admin_url('admin-ajax.php') . '", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({
                    action: "trial_booking_session",
                    slot_id: slotId
                })
            })
            .then(async res => {
                const text = await res.text();
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        // Show confirmation UI
                        alert("🎉 Trial session booked successfully!");
                        closeBookingModal();
                        window.location.reload(); // Refresh to update the calendar
                    } else {
                        alert(data.data?.message || "Booking failed.");
                    }
                } catch (err) {
                    console.error("Invalid JSON from server:", text);
                    alert("Unexpected server response. See console.");
                }
            })
            .catch(err => {
                console.error("Fetch failed:", err);
                alert("Error booking trial.");
            });
        });
        </script>';

        

    return $output;
}
add_shortcode('browse_tutors', 'browse_tutors_shortcode');


function quickbooks_booking_confirm() {
    if (!is_user_logged_in()) {
        return '<div class="booking-box error">🚫 Please log in to confirm your booking.</div>';
    }

    $slot_id = isset($_GET['slot_id']) ? intval($_GET['slot_id']) : 0;
    $subject = sanitize_text_field($_GET['subject'] ?? '');
    $duration = sanitize_text_field($_GET['duration'] ?? '');

    if (!$slot_id || empty($subject) || empty($duration)) {
        return '<div class="booking-box error">⚠️ Missing booking details.</div>';
    }

    global $wpdb;
    $user = wp_get_current_user();

    $table = $wpdb->prefix . 'tutor_availability';
    $slot = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $slot_id));

    if (!$slot || $slot->is_booked) {
        return '<div class="booking-box error">❗ This slot is already booked or invalid.</div>';
    }

    // Ensure end time exists
    $start_timestamp = strtotime("{$slot->date} {$slot->start_time}");
    $end_timestamp = strtotime("{$slot->date} {$slot->end_time}") ?: strtotime('+30 minutes', $start_timestamp);
    if (empty($slot->end_time)) {
        $wpdb->update($table, ['end_time' => date('H:i:s', $end_timestamp)], ['id' => $slot_id]);
    }

    // Update slot
    $wpdb->update($table, [
        'student_id' => $user->ID,
        'status'     => 'Booked',
        'meet_link'  => '',
        'booked_at'  => current_time('mysql'),
        'is_booked'  => 1
    ], ['id' => $slot_id]);

    // Format time nicely
    $time_display = date('g:i A', $start_timestamp);
    $date_display = date('F j, Y', $start_timestamp);

    // Build HTML
    ob_start();
    ?>
    <div class="booking-box success">
        <div class="checkmark">✅</div>
        <h2>Booking Confirmed!</h2>
        <p><strong>Student:</strong> <?php echo esc_html($user->display_name); ?></p>
        <p><strong>Subject:</strong> <?php echo esc_html($subject); ?></p>
        <p><strong>Duration:</strong> <?php echo esc_html($duration); ?></p>
        <p><strong>Date:</strong> <?php echo esc_html($date_display); ?></p>
        <p><strong>Time:</strong> <?php echo esc_html($time_display); ?></p>
        <p class="next-step">You’ll be contacted shortly to confirm your session details. 💬</p>
        <a href="/dashboard" class="btn-go-back">← Go to Dashboard</a>
    </div>
    <style>
        .booking-box {
            max-width: 500px;
            margin: 40px auto;
            background: #f4fef4;
            border: 2px solid #cceacc;
            border-radius: 10px;
            padding: 30px;
            font-family: 'Segoe UI', sans-serif;
            text-align: center;
        }
        .booking-box.success {
            background: #f0fff0;
            border-color: #b6e8b6;
        }
        .booking-box.error {
            background: #fff0f0;
            border-color: #e8b6b6;
        }
        .booking-box .checkmark {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .booking-box h2 {
            color: #2e7d32;
            margin-bottom: 16px;
        }
        .booking-box p {
            font-size: 16px;
            margin: 8px 0;
        }
        .booking-box .next-step {
            margin-top: 20px;
            font-style: italic;
            color: #444;
        }
        .btn-go-back {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 16px;
            background-color: #2e7d32;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
        }
        .btn-go-back:hover {
            background-color: #1b5e20;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('quickbooks_booking_confirm', 'quickbooks_booking_confirm');


add_shortcode('booking_confirmation', 'custom_booking_confirmation');
function custom_booking_confirmation() {
    ob_start();
    ?>
    <div class="booking-confirmation-wrapper" style="text-align:center;padding:40px;">
        <h2>🎉 Booking Confirmed</h2>
        <p>Thank you for your payment. We're now reserving your session.</p>
        <div id="booking-status" style="margin-top:20px;"></div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const urlParams = new URLSearchParams(window.location.search);
        const slotId = urlParams.get("slot_id");

        if (!slotId) {
            document.getElementById("booking-status").innerText = "Missing slot information.";
            return;
        }

        fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                action: "trial_booking_session",
                slot_id: slotId
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("booking-status").innerHTML = "<p style='color:green;'>Your session has been successfully booked! ✅</p>";
            } else {
                document.getElementById("booking-status").innerHTML = "<p style='color:red;'>Booking failed: " + (data.data.message || "Unknown error") + "</p>";
            }
        })
        .catch(err => {
            console.error("Error:", err);
            document.getElementById("booking-status").innerText = "Something went wrong. Please contact support.";
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
