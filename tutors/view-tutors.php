<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function tutors_directory_shortcode()
{
    wp_enqueue_style(
        'tutors-directory-css',
        plugins_url('/css/tutors-directory.css', __FILE__),
        array(),
        '1.0.0',
        'all'
    );

    
    $users = get_users();

    ob_start();
    echo '<div class="tutors-list">';

    foreach ($users as $user) {
        if (!in_array('tutor', $user->roles)) continue;

        // Check if the user is online
        $online_status = get_user_meta($user->ID, 'tutor_online_status', true);
        if (strtolower($online_status) === 'offline') continue;

        // Check if the user is available for booking
        $user_id = $user->ID;
        $name = esc_html($user->display_name);
        $avatar = get_user_meta($user_id, 'custom_profile_avatar', true);
        if (!$avatar) {
            $avatar = get_avatar_url($user_id);
        }

        $subjects = get_user_meta($user_id, 'subjects_handled', true);
        $rating = get_user_meta($user_id, 'rating', true);
        $rating_count = get_user_meta($user_id, 'rating_count', true);
        $lesson_count = get_user_meta($user_id, 'lesson_count', true);

        echo '<div class="tutor-card">';
        echo '<div class="tutor-card-header">';
        echo '<div class="tutor-photo-wrapper">';
        echo '<img src="' . esc_url($avatar) . '" alt="' . $name . '" class="tutor-photo">';

        if ($subjects) {
            $subject_list = explode(',', $subjects);
            foreach ($subject_list as $i => $subj) {
                if ($i >= 2) break; // Show only up to 2 subjects max
                echo '<span class="badge badge-subject floated-left" style="top: ' . (12 + $i * 28) . 'px;">' . esc_html(trim($subj)) . '</span>';
            }
        } else {
            echo '<span class="badge badge-subject floated-left">N/A</span>';
        }

        echo '<span class="badge badge-role floated-right">K-12 Teacher</span>';
        echo '</div>';
        echo '</div>';

        echo '<div class="tutor-card-body">';
        echo '<h3 class="tutor-name">' . $name . '</h3>';
        echo '<div class="tutor-rating">';
        echo '<span class="star">⭐</span>';
        echo '<span class="rating-number">' . esc_html($rating ?: 'N/A') . '</span>';
        echo '<span class="rating-count">(' . esc_html($rating_count ?: '0') . ')</span>';
        echo '</div>';
        echo '<div class="tutor-lessons">';
        echo '<strong>' . esc_html($lesson_count ?: '0') . '+</strong> Lessons';
        echo '</div>';
        echo '<div class="button-group">';
        echo '<button class="btn-trial" onclick="">Book Trial Teach</button>';
        echo '<button class="btn-tutor"><span class="calendar-icon">📅</span> Book Tutor</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    echo '</div>';
    return ob_get_clean();
}
add_shortcode('tutors_directory', 'tutors_directory_shortcode');

function available_tutors_shortcode()
{
    wp_enqueue_style('tutors-available-css', plugins_url('/css/tutors-available.css', __FILE__));
    wp_enqueue_script('tutors-available.js', plugins_url('/js/tutors-available.js', __FILE__), array(), '1.0', true);

    $users = get_users();
    ob_start();

    echo '<div class="tutors-grid">';
    foreach ($users as $user) {
        if (!in_array('tutor', $user->roles)) continue;

        $user_id = $user->ID;

        // ✅ Check online status
        $online_status = get_user_meta($user_id, 'tutor_online_status', true);
        if (!$online_status || $online_status !== 'online') continue;

        $name = esc_html($user->display_name);
        $avatar = get_user_meta($user_id, 'custom_profile_avatar', true);
        if (!$avatar) {
            $avatar = get_avatar_url($user_id);
        }

        $subjects = get_user_meta($user_id, 'subjects_handled', true);
        $rating = get_user_meta($user_id, 'rating', true);
        $rating_count = get_user_meta($user_id, 'rating_count', true);
        $lesson_count = get_user_meta($user_id, 'lesson_count', true);
        $video_url = get_user_meta($user_id, 'video_intro_url', true);
        $first_name = get_user_meta($user_id, 'first_name', true);
        $nickname = get_user_meta($user_id, 'nickname', true);
        
        if (!$first_name) {
            $first_name = $nickname;
        }
        if (!$video_url) {
            $video_url = plugins_url('/assets/intro.mp4', __FILE__);
        }

        echo '<div class="tutor-card">';
        echo '<div class="tutor-photo-wrapper">';
        echo '<div class="online-indicator" title="Online"></div>';
        echo '<img src="' . esc_url($avatar) . '" class="tutor-photo" data-video="' . esc_url($video_url) . '" />';
        echo '<div class="play-icon">▶️</div>';
        echo '</div>';

        echo '<div class="tutor-info">';
        echo '<div class="badge-container">';

        if ($subjects) {
            foreach (explode(',', $subjects) as $subj) {
                echo '<span class="badge badge-subject">' . esc_html(trim($subj)) . '</span>';
            }
        }

        echo '<span class="badge badge-role">K-12 Teacher</span>';
        echo '</div>';
        echo '<h3 class="tutor-name">' . 'Teacher ' . $first_name . '</h3>';
        echo '<div class="tutor-meta">';
        echo '<div class="tutor-rating"><span class="star">⭐</span>' . esc_html($rating ?: 'N/A') . ' (' . esc_html($rating_count ?: '0') . ')</div>';
        echo '<div class="tutor-lessons"><strong>' . esc_html($lesson_count ?: '0') . '+</strong> Lessons</div>';
        echo '</div>';
        echo '<div class="button-group">';
        echo '<button class="btn-trial" onclick="handleBooking()"><span class="calendar-icon">⏱️</span> Book Trial</button>';
        echo '<button class="btn-tutor" onclick="handleBooking()"><span class="calendar-icon">📅</span> Book Tutor</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';

    echo '<div id="videoModal" class="video-modal">';
    echo '<div class="video-modal-content">';
    echo '<span class="video-modal-close">&times;</span>';
    echo '<div id="videoContainer"></div>';
    echo '</div></div>';

?>
    <script>
        const isLoggedIn = <?php echo json_encode(is_user_logged_in()); ?>;

        function handleBooking() {
            if(!isLoggedIn){
                alert("Please login to book a trial lession.");
                window.location.href = "<?php echo home_url() ?>";
                return;
            }

            window.location.href = "/student/my-calendar/";
        }
    </script>

    
    
<?php
    return ob_get_clean();
}
add_shortcode('available_tutors', 'available_tutors_shortcode');



function parents_available_tutor_shortcode()
{
    wp_enqueue_style('tutors-available-css', plugins_url('/css/tutors-available.css', __FILE__));
    wp_enqueue_script('tutors-available.js', plugins_url('/js/tutors-available.js', __FILE__), array(), '1.0', true);

    $users = get_users();
    ob_start();

    echo '<div class="tutors-grid">';
    foreach ($users as $user) {
        if (!in_array('tutor', $user->roles)) continue;

        $user_id = $user->ID;

        $online_status = get_user_meta($user_id, 'tutor_online_status', true);
        if (!$online_status || $online_status !== 'online') continue;

        $name = esc_html($user->display_name);
        $avatar = get_user_meta($user_id, 'custom_profile_avatar', true);
        if (!$avatar) {
            $avatar = get_avatar_url($user_id);
        }

        $subjects = get_user_meta($user_id, 'subjects_handled', true);
        $rating = get_user_meta($user_id, 'rating', true);
        $rating_count = get_user_meta($user_id, 'rating_count', true);
        $lesson_count = get_user_meta($user_id, 'lesson_count', true);
        $video_url = get_user_meta($user_id, 'video_intro_url', true);
        $first_name = get_user_meta($user_id, 'first_name', true);
        $nickname = get_user_meta($user_id, 'nickname', true);

        if (!$first_name) {
            $first_name = $nickname;
        }
        if (!$video_url) {
            $video_url = plugins_url('/assets/intro.mp4', __FILE__);
        }

        echo '<div class="tutor-card">';
        echo '<div class="tutor-photo-wrapper">';
        echo '<div class="online-indicator" title="Online"></div>';
        echo '<img src="' . esc_url($avatar) . '" class="tutor-photo" data-video="' . esc_url($video_url) . '" />';
        echo '<div class="play-icon">▶️</div>';
        echo '</div>';

        echo '<div class="tutor-info">';
        echo '<div class="badge-container">';

        if ($subjects) {
            foreach (explode(',', $subjects) as $subj) {
                echo '<span class="badge badge-subject">' . esc_html(trim($subj)) . '</span>';
            }
        }

        echo '<span class="badge badge-role">K-12 Teacher</span>';
        echo '</div>';
        echo '<h3 class="tutor-name">' . 'Teacher ' . $first_name . '</h3>';
        echo '<div class="tutor-meta">';
        echo '<div class="tutor-rating"><span class="star">⭐</span>' . esc_html($rating ?: 'N/A') . ' (' . esc_html($rating_count ?: '0') . ')</div>';
        echo '<div class="tutor-lessons"><strong>' . esc_html($lesson_count ?: '0') . '+</strong> Lessons</div>';
        echo '</div>';

        echo '<div class="button-group">';
        echo '<button class="btn-trial" onclick="showTutorAvailability(' . esc_js($user_id) . ')"><span class="calendar-icon">⏱️</span> Book Trial</button>';
        echo '<button class="btn-tutor" onclick="openPaidBookingModal(' . esc_js($user_id) . ')"><span class="calendar-icon">📅</span> Book Tutor</button>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    echo '</div>';

    echo '<div id="videoModal" class="video-modal">';
    echo '<div class="video-modal-content">';
    echo '<span class="video-modal-close">&times;</span>';
    echo '<div id="videoContainer"></div>';
    echo '</div></div>';

    echo '<div id="availabilityModal" class="availability-modal" style="display:none;">';
    echo '<div class="modal-content">';
    echo '<span class="modal-close" onclick="closeAvailabilityModal()">&times;</span>';
    echo '<h3 class="modal-title">Available Slots</h3>';
    echo '<div id="availabilityContainer">Loading...</div>';
    echo '</div></div>';

    echo '<style>
    .availability-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0; top: 0; width: 100%; height: 100%;
        background-color: rgba(0,0,0,0.5);
        justify-content: center; align-items: center;
    }
    .availability-modal .modal-content {
        background: #fff;
        padding: 20px;
        margin: auto;
        border-radius: 10px;
        max-width: 500px;
    }
    .modal-close {
        float: right;
        font-size: 20px;
        cursor: pointer;
    }
    .book-slot-button {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 4px 10px;
    font-size: 12px;
    border-radius: 4px;
    cursor: pointer;
    margin-top: 5px;
    }
    .book-slot-button:hover {
        background-color: #218838;
    }

    </style>';


    echo '<script>
    function showTutorAvailability(tutorId) {
        const modal = document.getElementById("availabilityModal");
        const container = document.getElementById("availabilityContainer");
        modal.style.display = "block";
        container.innerHTML = "Loading...";

        fetch("' . admin_url('admin-ajax.php') . '?action=get_tutor_slots&tutor_id=" + tutorId)
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
            })
            .catch(() => {
                container.innerHTML = "Failed to load slots.";
            });
    }

    function closeAvailabilityModal() {
        document.getElementById("availabilityModal").style.display = "none";
    }
    </script>';

    echo '<script>
    function bookThisSlot(slotId) {
        if (!confirm("Confirm booking this time slot?")) return;

        fetch("' . admin_url('admin-ajax.php') . '", {
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
                alert("🎉 Booking successful!");
                closeAvailabilityModal();
                window.location.reload();
            } else {
                alert(data.data?.message || "Booking failed.");
            }
        })
        .catch(err => {
            console.error("Error:", err);
            alert("An error occurred while booking.");
        });
    }
    </script>';


    return ob_get_clean();
}
add_shortcode('parents_available_tutor', 'parents_available_tutor_shortcode');


add_action('wp_ajax_get_tutor_slots', 'get_tutor_slots_callback');
function get_tutor_slots_callback() {
    global $wpdb;

    $tutor_id = intval($_GET['tutor_id']);
    $table = $wpdb->prefix . 'tutor_availability';

    $start_date = date('Y-m-d');
    $dates = [];
    for ($i = 0; $i < 7; $i++) {
        $dates[] = date('Y-m-d', strtotime("+$i days"));
    }

    $placeholders = implode(',', array_fill(0, count($dates), '%s'));

    $query = $wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d AND status = 'Available' AND date IN ($placeholders) ORDER BY date, start_time",
        array_merge([$tutor_id], $dates)
    );

    $results = $wpdb->get_results($query, ARRAY_A);

    if (!$results) {
        echo '<p>No available slots for this tutor.</p>';
        wp_die();
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

    echo '<div class=\"availability-calendar\">';
    echo '<table class=\"availability-table\"><thead><tr><th>Time</th>';
    foreach ($dates as $date) {
        echo '<th>' . date('D', strtotime($date)) . '<br>' . date('m/d', strtotime($date)) . '</th>';
    }
    echo '</tr></thead><tbody>';

    foreach ($all_times as $time) {
        echo '<tr><td>' . $time['label'] . '</td>';
        foreach ($dates as $date) {
            $cell = '';
            if (!empty($slots[$time['value']][$date])) {
                foreach ($slots[$time['value']][$date] as $slot) {
                    $time_label = date('h:i A', strtotime($slot['start_time']));
                    $cell .= '<div class="slot-item" data-slot-id="' . esc_attr($slot['id']) . '">
                                <strong>' . esc_html($time_label) . '</strong><br>
                                <button class="book-slot-button" onclick="bookThisSlot(' . intval($slot['id']) . ')">Book</button>
                            </div>';

                }
            } else {
                $cell = '<div class=\"slot-closed\">—</div>';
            }
            echo '<td>' . $cell . '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody></table></div>';

    wp_die();
}


