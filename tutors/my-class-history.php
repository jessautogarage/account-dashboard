<?php
if(!defined('ABSPATH')) exit; // Exit if accessed directly

function my_class_history_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to view your class history.</p>';
    }

    $current_user = wp_get_current_user();
    if (!in_array('tutor', $current_user->roles)) {
        return '<p>Only tutors can view their class history.</p>';
    }

    wp_enqueue_style(
        'my-class-history-css',
        plugins_url('/css/my-class-history.css', __FILE__),
        array(),
        '1.0.0'
    );

    global $wpdb;
    $tutor_id = get_current_user_id();

    $classes = $wpdb->get_results($wpdb->prepare("
        SELECT a.*, u.display_name AS student_name, u.user_email
        FROM {$wpdb->prefix}tutor_availability a
        LEFT JOIN {$wpdb->prefix}users u ON a.student_id = u.ID
        WHERE a.user_id = %d AND a.is_booked = 1
        ORDER BY a.date DESC, a.start_time DESC
    ", $tutor_id));

    if (empty($classes)) {
        return '<p>You have no completed class history yet.</p>';
    }

    ob_start();
    echo '<div class="class-history-wrapper">';
    echo '<h3 class="history-title">My Class History</h3>';
    echo '<div class="table-responsive">';
    echo '<table class="class-history-table">';
    echo '<thead><tr>';
    echo '<th>Lesson Date</th>';
    echo '<th>Student’s Name</th>';
    echo '<th>Class Medium</th>';
    echo '<th>Course</th>';
    echo '<th>Level Assessment</th>';
    echo '<th>Lesson Memo</th>';
    echo '<th>Absent</th>';
    echo '<th>Lesson Attendance</th>';
    echo '<th>Mission Result</th>';
    echo '<th>Operation</th>';
    echo '</tr></thead><tbody>';

    foreach ($classes as $class) {
        $datetime = date('Y-m-d', strtotime($class->date)) . ' ' . date('H:i', strtotime($class->start_time)) . '–' . date('H:i', strtotime($class->end_time));
        $student = esc_html($class->student_name ?: 'Unknown');
        $class_medium = esc_html($class->class_medium ?? '--');
        $course = esc_html($class->course_name ?? 'Review');
        $level = esc_html($class->level_assessment ?? '-');
        $memo = !empty($class->memo_posted_at) 
            ? '<a href="#">Posted<br>' . date('m/d H:i', strtotime($class->memo_posted_at)) . '</a>'
            : '-';
        $absent = $class->absent == 1 ? 'Mark Student Absent' : '-';
        $attendance = $class->attended == 1 ? '<span class="badge success">Full Attendance</span>' : '-';
        $details_link = '<a href="#" class="view-link">View Details</a>';

        $operation_links = '
            <a href="#">Lesson Recorded</a><br>
            <a href="#">Evaluate</a><br>
            <a href="#">Materials</a><br>
            <a href="#">Tel Reminder</a>
        ';

        echo "<tr>
            <td>$datetime</td>
            <td>$student</td>
            <td>$class_medium</td>
            <td>$course</td>
            <td>$level</td>
            <td>$memo</td>
            <td>$absent</td>
            <td>$attendance<br>$details_link</td>
            <td>-</td>
            <td>$operation_links</td>
        </tr>";
    }

    echo '</tbody></table></div></div>';
    return ob_get_clean();
}
add_shortcode('my_class_history', 'my_class_history_shortcode');

