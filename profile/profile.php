<?php
if(!defined('ABSPATH')){
    exit; // Exit if accessed directly
}

// Shortcode to display tutor profile
function show_tutors_profile_shortcode(){
    $current_user = wp_get_current_user();
    $roles = array_map('ucfirst',$current_user->roles);
    $user_id = $current_user->ID;

    $first_name = esc_attr(get_user_meta($user_id, 'first_name', true));
    $nickname = esc_attr(get_user_meta($user_id, 'nickname', true));

    $custom_default = esc_url( get_site_url() . '/wp-content/uploads/2025/03/user.png' );
    //$avatar = get_avatar_url($user_id) !== get_option('avatar_default') ? get_avatar_url($user_id) : $custom_default;
    $avatar = get_user_meta($user_id, 'custom_profile_avatar', true);
    if (!$avatar) {
        $avatar = get_avatar_url($user_id);
    }
    if (!$avatar || $avatar === get_option('avatar_default')) {
        $avatar = $custom_default;
    }

    ob_start();
    ?>
    
        <div class="container-fluid p-3 position-relative">

            <?php if ( is_user_logged_in() ) : ?>
                <div style="position: absolute; top: 10px; right: 10px; background-color: #464F27; color: white; padding: 5px 10px; border-radius: 10px; font-size: 12px;">
                    <a href="<?php echo esc_url( home_url( '/tutors/profile/' ) ); ?>" style="color: white; text-decoration: none;">✏️ Edit Profile</a>
                </div>
            <?php endif; ?>
            <div class="profile-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <img src="<?php echo $avatar;?>" class="profile-image"/>
                    <div class="ms-3">
                        <div class="profile-name fs-6" style="color: #D0D755">
                            Good day, Teacher <?php echo ucfirst(!empty($nickname) ? $nickname : $first_name); ?>
                        </div>

                        <?php
                        $status = get_user_meta($user_id, 'tutor_online_status', true);
                        $status = $status === 'online' ? 'online' : 'offline';
                        ?>

                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input tutor-status-toggle" type="checkbox" role="switch" id="tutorStatusToggle"
                                <?php echo $status === 'online' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="tutorStatusToggle" style="color: <?php echo $status === 'online' ? '#28a745' : '#6c757d'; ?>">
                                <?php echo ucfirst($status); ?>
                            </label>
                        </div>

                        
                        <?php
                        $subjects = get_user_meta($user_id, 'subjects_handled', true);
                        if ($subjects) {
                            $subject_list = array_map('trim', explode(',', $subjects));
                            foreach ($subject_list as $subj) {
                                echo '<div class="profile-details mt-2">
                                    <button style="border:solid 2px; border-radius:20px; border-color: #464F27 1px; color: #D0D755;">
                                        <strong>' . esc_html($subj) . '</strong>
                                    </button>
                                </div>';
                            }
                        } else {
                            echo '<div class="profile-details mt-2">
                                <button style="border:solid 2px; border-radius:20px; border-color: #464F27 1px; color: #D0D755;">
                                    <strong>N/A</strong>
                                </button>
                            </div>';
                        }
                        ?>

                        
                        <div class="profile-details mt-2"><button style="border:solid 2px; border-radius:20px; border-color: #464F27 1px; color: #D0D755;"><strong>K-12 Teacher</strong></button></div>
                    </div>
                </div>
                <div style="border-left: 3px solid #D0D755; padding-left: 10px;">
                    <div class="text-center">
                        <span class="text-dark"><strong>Favorite:</strong> <span style="color: #464F27;">28</span></span>
                        <span class="ms-3 text-dark"><strong>Followers:</strong> <span style="color: #464F27;">28</span></span>
                    </div>
                    <p class="fs-6"><strong>Evaluated Lessons</strong><br/><strong style="color: #D0D755">12</strong></p>
                </div>
                <div style="border-left: 3px solid #D0D755; padding-left: 10px;">
                    <div class="text-center">
                        &nbsp;
                    </div>
                    <p class="fs-6"><strong>Satisfaction Rate</strong><br/><strong style="color: #D0D755">95%</strong></p>
                </div>
                <div class="d-flex flex-column align-items-center">
                    <div class="buttons-container mt-3">
                        <button class="btn" style="background-color: #D0D755">Contact Admin</button>
                        <button class="btn" style="background-color: #464F27; color:white">My Classroom</button>
                    </div>
                </div>
                <div>
                    &nbsp;
                </div>
            </div>
        </div>

    
    <?php
    return ob_get_clean();
}
add_shortcode('show_tutors_profile', 'show_tutors_profile_shortcode');

//Tutor Status Toggle
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('tutor-status-toggle', plugins_url('/js/tutor-status-toggle.js', __FILE__), ['jquery'], '1.0', true);
    wp_localize_script('tutor-status-toggle', 'tutorStatusAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('tutor_status_nonce')
    ]);
});
// Tutor status toggle AJAX handler
add_action('wp_ajax_toggle_tutor_status', function () {
    check_ajax_referer('tutor_status_nonce', 'nonce');

    $user_id = get_current_user_id();
    if (!$user_id || !current_user_can('tutor')) {
        wp_send_json_error('Unauthorized', 403);
    }

    $new_status = $_POST['status'] === 'online' ? 'online' : 'offline';
    update_user_meta($user_id, 'tutor_online_status', $new_status);
    wp_send_json_success(['status' => $new_status]);
});




//Tutors Profile update sa Tutor Profile Page
function show_tutors_update_profile_shortcode($atts) {
    wp_enqueue_style('profile-update-css', plugins_url('/css/profile-update.css', __FILE__), [], '1.0');
    wp_enqueue_script('cropper-js', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js', [], null, true);
    wp_enqueue_style('cropper-css', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css');
    wp_enqueue_script('profile-update-js', plugins_url('/js/profile-update.js', __FILE__), [], '1.0', true);

    $atts = shortcode_atts(['user_id' => get_current_user_id()], $atts);
    $user_id = intval($atts['user_id']);
    $user = get_user_by('id', $user_id);
    if (!$user || !in_array('tutor', $user->roles)) return '<p class="text-danger">Tutor not found.</p>';

    $current_user_id = get_current_user_id();
    $is_editable = ($current_user_id === $user_id);

    //$name = esc_attr($user->display_name);
    $first_name = esc_attr(get_user_meta($user_id, 'first_name', true));
    $last_name = esc_attr(get_user_meta($user_id, 'last_name', true));

    $email = esc_attr($user->user_email);
    $username = esc_attr($user->user_login);
    $nickname = esc_attr(get_user_meta($user_id, 'nickname', true));

    $avatar = get_user_meta($user_id, 'custom_profile_avatar', true) ?: get_avatar_url($user_id);
    $subjects = get_user_meta($user_id, 'subjects_handled', true);
    $video_url = get_user_meta($user_id, 'video_intro_url', true);

    //I just comment this for future use
    // if (!empty($_POST['user_login']) && $_POST['user_login'] !== $user->user_login) {
    //     wp_update_user(['ID' => $user_id, 'user_login' => sanitize_user($_POST['user_login'])]);
    // }
    

    if ($is_editable && isset($_POST['save_tutor_profile']) && check_admin_referer('update_tutor_profile')) {
        if (!empty($_POST['first_name']) || !empty($_POST['last_name'])) {
            $first_name = sanitize_text_field($_POST['first_name']);
            $last_name = sanitize_text_field($_POST['last_name']);
        
            update_user_meta($user_id, 'first_name', $first_name);
            update_user_meta($user_id, 'last_name', $last_name);
        
            $display_name = trim($first_name . ' ' . $last_name);
            wp_update_user(['ID' => $user_id, 'display_name' => $display_name]);
        }

        if (!empty($_POST['nickname'])) {
            $nickname = sanitize_text_field($_POST['nickname']);
            wp_update_user([
                'ID' => $user_id,
                'nickname' => $nickname,
            ]);
        }
        
        

        if (!empty($_POST['video_intro_url'])) {
            update_user_meta($user_id, 'video_intro_url', esc_url_raw($_POST['video_intro_url']));
            $video_url = esc_url_raw($_POST['video_intro_url']);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'tutor_subjects';
        $raw_subjects = isset($_POST['subjects_handled']) ? trim($_POST['subjects_handled']) : '';
        update_user_meta($user_id, 'subjects_handled', sanitize_text_field($raw_subjects));
        $wpdb->delete($table_name, ['user_id' => $user_id]);

        if (!empty($raw_subjects)) {
            $subject_list = array_map('trim', explode(',', $raw_subjects));
            foreach ($subject_list as $subject) {
                if (!empty($subject)) {
                    $wpdb->insert(
                        $table_name,
                        [
                            'user_id'     => $user_id,
                            'subject'     => sanitize_text_field($subject),
                            'price'       => 3.50,
                            'created_at'  => current_time('mysql'),
                        ],
                        ['%d', '%s', '%f', '%s']
                    );
                }
            }
        }

        if (!empty($_POST['new_password'])) {
            wp_set_password($_POST['new_password'], $user_id);
            wp_logout();
            wp_redirect(wp_login_url());
            exit;
        }

        if (!empty($_FILES['profile_avatar']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $uploaded = media_handle_upload('profile_avatar', 0);
            if (!is_wp_error($uploaded)) {
                $url = wp_get_attachment_url($uploaded);
                update_user_meta($user_id, 'custom_profile_avatar', $url);
                $avatar = $url;
            }
        }

        echo '<div class="alert alert-success mt-3">Profile updated successfully.</div>';
    }

    ob_start(); ?>

    <div class="container my-4">
        <h2 class="mb-4">My Tutor Profile</h2>
        <form method="post" enctype="multipart/form-data">
            <?php if ($is_editable) wp_nonce_field('update_tutor_profile'); ?>

            <div class="row">
                <div class="col-md-7">
                    <div class="mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" name="first_name" value="<?php echo $first_name; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" value="<?php echo $last_name; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nickname</label>
                        <input type="text" class="form-control" name="nickname" value="<?php echo $nickname; ?>">
                    </div>


                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" value="<?php echo $email; ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo $username; ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subjects Taught (comma-separated)</label>
                        <input type="text" class="form-control" name="subjects_handled" value="<?php echo esc_attr($subjects); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Change Password</label>
                        <input type="password" class="form-control" name="new_password" placeholder="Enter new password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload Photo</label>
                        <input type="file" class="form-control" name="profile_avatar" id="profile_avatar" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Intro Video URL (YouTube)</label>
                        <input type="url" class="form-control" name="video_intro_url" id="video_input" value="<?php echo esc_attr($video_url); ?>">
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="save_tutor_profile" class="btn" style="background-color: #464F27; color: white; border:none;">Save Changes</button>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="mb-4">
                        <label class="form-label">Current Profile Photo</label><br>
                        <img src="<?php echo esc_url($avatar); ?>" alt="Profile Photo" id="avatar_preview" class="img-thumbnail" style="max-width: 200px;">
                    </div>

                    <div>
                        <label class="form-label">Introductory Video Preview</label>
                        <div id="video_preview" class="ratio ratio-16x9">
                            <?php if ($video_url): ?>
                                <?php
                                    preg_match(
                                        '%(?:youtube\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/|youtube\.com/shorts/)([^"&?/ ]{11})%',
                                        $video_url,
                                        $matches
                                    );
                                    $youtube_id = $matches[1] ?? '';
                                ?>
                                <?php if ($youtube_id): ?>
                                    <iframe src="https://www.youtube.com/embed/<?php echo esc_attr($youtube_id); ?>" allowfullscreen></iframe>
                                <?php else: ?>
                                    <p class="text-danger">Invalid YouTube URL</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-muted">No video uploaded.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php return ob_get_clean();
}
add_shortcode('show_tutors_update_profile', 'show_tutors_update_profile_shortcode');








//Shortcode to display student profile
function show_student_profile_shortcode() {
    $current_user = wp_get_current_user();
    $roles = array_map('ucfirst', $current_user->roles);
    $user_id = $current_user->ID;

    $custom_default = esc_url(get_site_url() . '/wp-content/uploads/2025/03/user.png');
    $custom_avatar = get_user_meta($user_id, 'custom_profile_avatar', true);
    $avatar = $custom_avatar ?: get_avatar_url($user_id);

    if (!$custom_avatar && get_avatar_url($user_id) === get_option('avatar_default')) {
        $avatar = $custom_default;
    }

    global $wpdb;
    $student_table = $wpdb->prefix . 'student';

    $grade_level = $wpdb->get_var(
        $wpdb->prepare("SELECT grade_level FROM $student_table WHERE user_id = %d LIMIT 1", $user_id)
    ) ?: '';

    $nickname = $wpdb->get_var(
        $wpdb->prepare("SELECT nickname FROM $student_table WHERE user_id = %d LIMIT 1", $user_id)
    ) ?: '';

    $avs_number = $wpdb->get_var(
        $wpdb->prepare("SELECT avs_number FROM $student_table WHERE user_id = %d LIMIT 1", $user_id)
    ) ?: '';

    $first_name = esc_attr(get_user_meta($user_id, 'first_name', true));

    ob_start();
    ?>

    <style>
        .btn-tutor {
            background-color: #464F27;
            color: white;
            border: none;
        }
        .btn-tutor:hover {
            background-color: #3b401f;
            color: white;
        }
        .btn-gold {
            background-color: #D0D755;
            color: #464F27;
        }
        .btn-gold:hover {
            background-color: #c6ce50;
            color: #333;
        }
        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>

    <div class="container-fluid py-4 position-relative">

        <?php if (is_user_logged_in()) : ?>
            <div class="position-absolute top-0 end-0 me-3 mt-3" style="z-index: 999;">
                <a href="<?php echo esc_url(home_url('/student/profile/')); ?>" class="btn btn-tutor btn-sm">
                    ✏️ Edit Profile
                </a>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm rounded-4 p-4 w-100">
            <div class="row text-center align-items-center">

                <!-- Column 1: Avatar, name, grade -->
                <div class="col-md-3 d-flex align-items-center justify-content-start text-start">
                    <img src="<?php echo esc_url($avatar); ?>" class="profile-image me-3" alt="Avatar">
                    <div>
                        <h6 class="text-success mb-1">Good day, <?php echo ucfirst(!empty($nickname) ? $nickname : $first_name); ?></h6>
                        <span class="badge border border-success text-success px-3 py-2 rounded-pill">
                            <?php echo esc_html('AVS Number :' . $avs_number );?>
                        </span>
                    </div>
                </div>

                <!-- Column 2: Evaluated Lessons -->
                <div class="col-md-3 border-start">
                    <p class="mb-1 fw-bold">Evaluated Lessons</p>
                    <p class="text-success fw-bold mb-0">12</p>
                </div>

                <!-- Column 3: Satisfaction Rate -->
                <div class="col-md-3 border-start">
                    <p class="mb-1 fw-bold">Satisfaction Rate</p>
                    <p class="text-success fw-bold mb-0">95%</p>
                </div>

                <!-- Column 4: Buttons -->
                <div class="col-md-3 border-start">
                    <div class="d-grid gap-2">
                        <button class="btn btn-gold btn-sm w-100">Contact Admin</button>
                        <div class="text-muted small">Available Credits: <strong>$ 123</strong></div>
                        <a href="#"><button class="btn btn-tutor btn-sm w-100">My Classroom</button></a>
                        <a href="<?php echo home_url('/student/top-up/'); ?>"><button class="btn btn-tutor btn-sm w-100">Top Up Now</button></a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('show_student_profile', 'show_student_profile_shortcode');

// Function to get student profile data
function get_student_profile_data($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'student';

    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table WHERE user_id = %d LIMIT 1", $user_id),
        ARRAY_A
    );
}

// Function to save student profile data
function save_student_profile_data($user_id, $data) {
    global $wpdb;
    $table = $wpdb->prefix . 'student';

    $existing = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM $table WHERE user_id = %d", $user_id)
    );

    if ($existing) {
        return $wpdb->update(
            $table,
            $data,
            ['user_id' => $user_id],
            null,
            ['%d']
        );
    } else {
        $data['user_id'] = $user_id;
        return $wpdb->insert($table, $data);
    }
}



// Shortcode to display student update profile form
function show_student_update_profile_shortcode($atts) {
    wp_enqueue_style('student-profile-update-css', plugins_url('/css/student-profile-update.css', __FILE__), [], '1.0');

    $user_id = get_current_user_id();
    $user = get_user_by('id', $user_id);

    if (!$user || !in_array('student', $user->roles)) {
        return '<p>Student not found or not authorized.</p>';
    }

    if (
        isset($_POST['save_student_profile']) &&
        isset($_POST['student_profile_nonce']) &&
        wp_verify_nonce($_POST['student_profile_nonce'], 'student_profile_update')
    ) {
        $data = [
            'nickname'          => sanitize_text_field($_POST['nickname']),
            'physical_address'  => sanitize_text_field($_POST['physical_address']),
            'viber_number'      => sanitize_text_field($_POST['viber_number']),
            'date_of_birth'     => sanitize_text_field($_POST['date_of_birth']),
            'gender'            => sanitize_text_field($_POST['gender']),
            'likes'             => sanitize_text_field($_POST['likes']),
            'dislikes'          => sanitize_text_field($_POST['dislikes']),
        ];

        if (!empty($_POST['first_name']) && $_POST['first_name'] !== $user->first_name) {
            wp_update_user(['ID' => $user_id, 'first_name' => sanitize_text_field($_POST['first_name'])]);
        }
        if (!empty($_POST['last_name']) && $_POST['last_name'] !== $user->last_name) {
            wp_update_user(['ID' => $user_id, 'last_name' => sanitize_text_field($_POST['last_name'])]);
        }

        save_student_profile_data($user_id, $data);

        if (!empty($_FILES['profile_photo']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $uploaded = media_handle_upload('profile_photo', 0);
            if (!is_wp_error($uploaded)) {
                update_user_meta($user_id, 'custom_profile_avatar', wp_get_attachment_url($uploaded));
            }
        }

        $redirect_url = add_query_arg('updated', 'true', get_permalink());

        if (!headers_sent()) {
            wp_safe_redirect($redirect_url);
            exit;
        } else {
            echo "<script>window.location.href = '" . esc_url($redirect_url) . "';</script>";
            echo "<noscript><meta http-equiv='refresh' content='0;url=" . esc_url($redirect_url) . "'></noscript>";
            return '';
        }
    }

    $profile = get_student_profile_data($user_id);

    $first_name = $user->first_name ?? '';
    $last_name = $user->last_name ?? '';

    $nickname        = $profile['nickname'] ?? '';
    $physical_address = $profile['physical_address'] ?? '';
    $viber_number    = $profile['viber_number'] ?? '';
    $date_of_birth   = $profile['date_of_birth'] ?? '';
    $gender          = $profile['gender'] ?? '';
    $likes           = $profile['likes'] ?? '';
    $dislikes        = $profile['dislikes'] ?? '';
    $avs_number      = $profile['avs_number'] ?? '';

    $avatar = get_user_meta($user_id, 'custom_profile_avatar', true) ?: get_avatar_url($user_id);

    $subjects = [
        'Math' => ['95', '90', '92', '93'],
        'Science' => ['89', '88', '85', '87']
    ];

    ob_start();
    ?>

    <style>
        html, body {
            height: 100%;
            margin: 0;
        }
        .page-wrapper {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .page-content {
            flex: 1;
        }
        .student-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
        }
        .btn-tutor {
            background-color: #464F27;
            color: white;
        }
        .btn-tutor:hover {
            background-color: #3b401f;
            color: white;
        }
    </style>

    <div class="page-wrapper">
        <div class="page-content container my-4">
            <h2 class="mb-4">My Profile</h2>

            <?php if (isset($_GET['updated']) && $_GET['updated'] === 'true') : ?>
                <div class="alert alert-success">Profile updated successfully!</div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('student_profile_update', 'student_profile_nonce'); ?>
                <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-7">
                        <?php foreach ([
                            'first_name' => 'First Name',
                            'last_name' => 'Last Name',
                            'nickname' => 'Nickname',
                            'physical_address' => 'Physical Address',
                            'viber_number' => 'Viber Number',
                            'date_of_birth' => 'Date of Birth',
                            'gender' => 'Gender',
                            'likes' => 'Likes',
                            'dislikes' => 'Dislikes'
                        ] as $field => $label) : ?>
                            <div class="mb-3">
                                <label class="form-label"><?php echo $label; ?></label>
                                <input type="<?php echo $field === 'date_of_birth' ? 'date' : 'text'; ?>" 
                                class="form-control" 
                                name="<?php echo $field; ?>" 
                                value="<?php echo esc_attr($$field); ?>"
                                placeholder="Enter <?php echo strtolower(str_replace('_', ' ', $label)); ?>">

                            </div>
                        <?php endforeach; ?>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?php echo esc_attr($user->user_email); ?>" readonly>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-md-5">
                        <div class="mb-4 text-center">
                        <p><strong>Photo:</strong></p>
                        <div style="text-align: center;">
                            <img src="<?php echo esc_url($avatar); ?>" 
                                style="width: 120px; height: 120px; border-radius: 8px; object-fit: cover; display: block; margin: 0 auto 10px;" />

                            <label style="display: inline-block; padding: 6px 12px; border: 1px solid #ccc; border-radius: 4px; cursor: pointer; font-size: 14px; background-color: #f8f9fa;">
                                📷 Update Profile Photo
                                <input type="file" name="profile_photo" accept="image/*" style="display: none;">
                            </label>
                        </div>

                        </div>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="user_login" value="<?php echo esc_attr($user->user_login); ?>" required readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="btn btn-outline-warning w-100">Reset Password</a>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">AVS Number</label>
                            <input type="text" class="form-control" value="<?php echo esc_attr($avs_number); ?>" readonly>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Subjects</label>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Subject</th>
                                            <th>1st</th>
                                            <th>2nd</th>
                                            <th>3rd</th>
                                            <th>4th</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subjects as $subject => $grades) : ?>
                                            <tr>
                                                <td><?php echo esc_html($subject); ?></td>
                                                <?php foreach ($grades as $grade) : ?>
                                                    <td><?php echo esc_html($grade); ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm">Add Subject</button>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" name="save_student_profile" class="btn btn-tutor px-4">Save Changes</button>
                </div>

            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form');
            const saveButton = document.querySelector('button[name="save_student_profile"]');
            const inputs = form.querySelectorAll('input:not([readonly]):not([type="file"]), textarea, select');
            const fileInput = form.querySelector('input[type="file"][name="profile_photo"]');

            const originalValues = {};

            // Store original values
            inputs.forEach(input => {
                originalValues[input.name] = input.value;
            });

            function checkForChanges() {
                let changed = false;

                inputs.forEach(input => {
                    if (input.value !== originalValues[input.name]) {
                        changed = true;
                    }
                });

                // Enable button if file is selected
                if (fileInput.files.length > 0) {
                    changed = true;
                }

                saveButton.disabled = !changed;
            }

            // Listen for changes
            inputs.forEach(input => {
                input.addEventListener('input', checkForChanges);
                input.addEventListener('change', checkForChanges);
            });

            fileInput.addEventListener('change', checkForChanges);

            // Initially disable the button
            saveButton.disabled = true;
        });
    </script>



    <?php
    return ob_get_clean();
}
add_shortcode('show_student_update_profile', 'show_student_update_profile_shortcode');







// Shortcode to display parent profile
function show_parent_profile_shortcode(){
    $current_user = wp_get_current_user();
    $roles = array_map('ucfirst', $current_user->roles);
    $user_id = $current_user->ID;

    $custom_avatar = get_user_meta($user_id, 'custom_profile_avatar', true);
    $avatar = $custom_avatar ?: esc_url(get_avatar_url($user_id) ?: get_site_url() . '/wp-content/uploads/2025/03/user.png');


    $edit_profile_url = home_url('/parents/profile'); // Update if needed

    ob_start();
    ?>

    <div class="container-fluid p-3 position-relative">
        <!-- ✏️ Edit Profile pill button (no background on container) -->
        <a href="<?php echo esc_url($edit_profile_url); ?>" class="position-absolute top-0 end-0 mt-2 me-2 text-decoration-none">
            <span style="color: white; background-color: #464F27; padding: 6px 14px; border-radius: 20px; font-size: 13px;">
                ✏️ Edit Profile
            </span>
        </a>

        <div class="profile-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="<?php echo esc_url($avatar); ?>" class="profile-image"/>
                <div class="ms-3">
                    <div class="profile-name fs-6">Good day, <?php echo ucfirst($current_user->first_name); ?></div>
                    <div class="profile-details fs-5">Satisfaction Rate</div>
                    <div class="profile-details fs-5">95%</div>
                </div>
            </div>

            <div class="d-flex flex-column align-items-center">
                <div class="buttons-container mt-3">
                    <button class="btn" style="background-color: #D0D755;">Contact Admin</button>
                    <span>Available Credits: <strong>$ 123</strong></span><br>
                    <a href="#"><button class="btn" style="background-color: #464F27; color: white;">My Child's Classroom</button></a>
                    <a href="<?php echo esc_url(home_url('/parents/top-up/')); ?>"><button class="btn" style="background-color: #464F27; color: white;">Top Up Now</button></a>
                </div>
            </div>

            <div>&nbsp;</div>
            <div>&nbsp;</div>
            <div>&nbsp;</div>
        </div>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('show_parent_profile', 'show_parent_profile_shortcode');




// Shortcode to display parent update profile form with multi-child support
function show_parent_update_profile_shortcode($atts) {
    global $wpdb;

    // 🔐 Handle POST actions FIRST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_id = get_current_user_id();

        // 🔹 Save profile form
        if (
            isset($_POST['save_parent_profile']) &&
            wp_verify_nonce($_POST['parent_profile_nonce'], 'parent_profile_update')
        ) {
            wp_update_user([
                'ID' => $user_id,
                'first_name' => sanitize_text_field($_POST['first_name']),
                'last_name'  => sanitize_text_field($_POST['last_name']),
            ]);

            if (!empty($_POST['nickname'])) {
                update_user_meta($user_id, 'nickname', sanitize_text_field($_POST['nickname']));
            }

            $wpdb->update(
                "{$wpdb->prefix}parent",
                [
                    'physical_address' => sanitize_text_field($_POST['physical_address']),
                    'viber_number'     => sanitize_text_field($_POST['viber_number']),
                    'gender'           => sanitize_text_field($_POST['gender']),
                ],
                ['user_id' => $user_id],
                ['%s', '%s', '%s'],
                ['%d']
            );

            if (!empty($_FILES['profile_photo']['name'])) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';

                $uploaded = media_handle_upload('profile_photo', 0);
                if (!is_wp_error($uploaded)) {
                    update_user_meta($user_id, 'custom_profile_avatar', wp_get_attachment_url($uploaded));
                }
            }

            // wp_safe_redirect(home_url('parents/profile/') . '?updated=true');
            // exit;

            echo '<script>window.location.href = "' . esc_url(home_url('parents/profile/?updated=true')) . '";</script>';
            exit;

        }

        // 🔹 Link child form
        if (
            isset($_POST['link_child_submit']) &&
            isset($_POST['child_id']) &&
            !empty($_POST['child_id']) &&
            wp_verify_nonce($_POST['parent_profile_nonce'], 'parent_profile_update')
        ) {
            $student_id = intval($_POST['child_id']);

            $existing = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) FROM {$wpdb->prefix}parent
                WHERE user_id = %d AND student_id = %d
            ", $user_id, $student_id));

            if (!$existing) {
                $wpdb->insert(
                    "{$wpdb->prefix}parent",
                    ['user_id' => $user_id, 'student_id' => $student_id],
                    ['%d', '%d']
                );
            }

            // wp_safe_redirect(home_url('parents/profile/') . '?child_linked=true');
            // exit;
            echo '<script>window.location.href = "' . esc_url(home_url('parents/profile/?child_linked=true')) . '";</script>';
            exit;

        }
    }

    // ✅ Load parent data
    $user_id = get_current_user_id();
    $user = get_user_by('id', $user_id);

    if (!$user || !in_array('parent', $user->roles)) {
        return '<div class="alert alert-danger">Parent not found or not authorized.</div>';
    }

    wp_enqueue_style('parent-profile-update-css', plugins_url('/css/parent-profile-update.css', __FILE__), [], '1.0');

    $children = $wpdb->get_results($wpdb->prepare("
        SELECT p.student_id, u.display_name, 
            um1.meta_value AS first_name, 
            um2.meta_value AS last_name, 
            s.avs_number
        FROM {$wpdb->prefix}parent p
        JOIN {$wpdb->prefix}users u ON p.student_id = u.ID
        LEFT JOIN {$wpdb->prefix}usermeta um1 ON um1.user_id = u.ID AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->prefix}usermeta um2 ON um2.user_id = u.ID AND um2.meta_key = 'last_name'
        LEFT JOIN {$wpdb->prefix}student s ON s.user_id = p.student_id
        WHERE p.user_id = %d
    ", $user_id));

    $nickname    = get_user_meta($user_id, 'nickname', true);
    $avatar      = get_user_meta($user_id, 'custom_profile_avatar', true) ?: get_avatar_url($user_id);
    $first_name  = $user->first_name;
    $last_name   = $user->last_name;
    $email       = $user->user_email;
    $username    = $user->user_login;

    ob_start();
    ?>
    <div class="container my-4">
        <?php if (isset($_GET['updated'])) : ?>
            <div class="alert alert-success">Profile updated successfully!</div>
        <?php elseif (isset($_GET['child_linked'])) : ?>
            <div class="alert alert-info">Child linked successfully!</div>
        <?php endif; ?>

        <h2 class="mb-4 text-success">My Profile</h2>

        <!-- 🔹 Profile Form -->
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('parent_profile_update', 'parent_profile_nonce'); ?>
            <div class="row">
                <div class="col-md-7">
                    <?php foreach ([
                        'first_name'       => ['First Name', $first_name],
                        'last_name'        => ['Last Name', $last_name],
                        'nickname'         => ['Nickname', $nickname],
                        'physical_address' => ['Physical Address', $children[0]->physical_address ?? ''],
                        'viber_number'     => ['Viber Number', $children[0]->viber_number ?? ''],
                        'gender'           => ['Gender', $children[0]->gender ?? '']
                    ] as $field => [$label, $value]) : ?>
                        <div class="mb-3">
                            <label class="form-label"><?php echo esc_html($label); ?></label>
                            <input type="text" class="form-control" name="<?php echo $field; ?>" value="<?php echo esc_attr($value); ?>" placeholder="Enter <?php echo strtolower($label); ?>">
                        </div>
                    <?php endforeach; ?>

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" value="<?php echo esc_attr($email); ?>" readonly>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="mb-3 text-center">
                        <label class="form-label d-block">Photo:</label>
                        <img src="<?php echo esc_url($avatar); ?>" class="rounded" style="width: 120px; height: 120px; object-fit: cover;" />
                        <br>
                        <label class="btn btn-outline-secondary btn-sm mt-2">
                            📷 Update Profile Photo
                            <input type="file" name="profile_photo" accept="image/*" style="display: none;">
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" value="<?php echo esc_html($username); ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" value="************" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Linked Children</label>
                        <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($children as $child) : ?>
                                <div class="d-flex align-items-center mb-3 border-bottom pb-2">
                                    <img src="<?php echo esc_url(plugins_url('/img/user.png', __FILE__)); ?>" 
                                        alt="Child Photo" 
                                        class="me-3 rounded-circle" 
                                        style="width: 60px; height: 60px; object-fit: cover;">
                                    <div>
                                        <div><strong>Name:</strong> <?php echo esc_html(($child->first_name ?? '') . ' ' . ($child->last_name ?? '')); ?></div>
                                        <div><strong>AVS:</strong> <?php echo esc_html($child->avs_number); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="button" style="background-color: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 4px;" data-bs-toggle="modal" data-bs-target="#addChildModal">
                            Add Child
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-4">
            <button type="submit" name="save_parent_profile" style="background-color: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 4px;">
                Save Changes
            </button>

            </div>
        </form>

        <!-- 🔹 Modal Form -->
        <div class="modal fade" id="addChildModal" tabindex="-1" aria-labelledby="addChildModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="post">
                    <?php wp_nonce_field('parent_profile_update', 'parent_profile_nonce'); ?>
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Link Child by AVS Number</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3 position-relative">
                                <label class="form-label">Search AVS Number</label>
                                <input type="text" name="avs_search" id="avs_search" class="form-control" placeholder="Enter AVS Number..." autocomplete="off">
                                <div id="avs_suggestions" class="list-group mt-1 position-absolute w-100" style="z-index: 1000;"></div>
                            </div>
                            <input type="hidden" name="child_id" id="child_id">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="link_child_submit" class="btn btn-primary">Link Child</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";</script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const avsSearch = document.getElementById("avs_search");
        const suggestions = document.getElementById("avs_suggestions");
        const childIdInput = document.getElementById("child_id");

        avsSearch.addEventListener("input", function () {
            const query = this.value.trim();
            if (query.length < 3) {
                suggestions.innerHTML = '';
                return;
            }

            fetch(ajaxurl + "?action=search_avs_number&avs=" + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    suggestions.innerHTML = '';
                    const results = data?.data?.results || [];
                    if (data.success && results.length > 0) {
                        results.forEach(child => {
                            const item = document.createElement("div");
                            item.className = "list-group-item list-group-item-action";
                            item.textContent = `${child.name} (AVS: ${child.avs})`;
                            item.style.cursor = "pointer";
                            item.addEventListener("click", () => {
                                avsSearch.value = child.avs;
                                childIdInput.value = child.id;
                                suggestions.innerHTML = '';
                            });
                            suggestions.appendChild(item);
                        });
                    } else {
                        suggestions.innerHTML = '<div class="list-group-item">No match found</div>';
                    }
                })
                .catch(error => {
                    console.error("AVS fetch error:", error);
                    suggestions.innerHTML = '<div class="list-group-item text-danger">Search error</div>';
                });
        });

        document.addEventListener("click", function (e) {
            if (!avsSearch.contains(e.target) && !suggestions.contains(e.target)) {
                suggestions.innerHTML = '';
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('show_parent_update_profile', 'show_parent_update_profile_shortcode');



add_action('wp_ajax_search_avs_number', 'search_avs_number_callback');

function search_avs_number_callback() {
    global $wpdb;

    $avs = sanitize_text_field($_GET['avs']);

    if (empty($avs)) {
        wp_send_json_error(['message' => 'Empty AVS']);
    }

    $results = $wpdb->get_results($wpdb->prepare("
        SELECT u.ID, s.avs_number, um1.meta_value AS first_name, um2.meta_value AS last_name
        FROM {$wpdb->prefix}users u
        JOIN {$wpdb->prefix}usermeta um ON um.user_id = u.ID
        JOIN {$wpdb->prefix}student s ON s.user_id = u.ID
        LEFT JOIN {$wpdb->prefix}usermeta um1 ON um1.user_id = u.ID AND um1.meta_key = 'first_name'
        LEFT JOIN {$wpdb->prefix}usermeta um2 ON um2.user_id = u.ID AND um2.meta_key = 'last_name'
        WHERE um.meta_key = '{$wpdb->prefix}capabilities'
        AND um.meta_value LIKE '%%student%%'
        AND s.avs_number LIKE %s
        LIMIT 5
    ", '%' . $wpdb->esc_like($avs) . '%'));

    if (!$results) {
        wp_send_json_success(['results' => []]);
    }

    $formatted = array_map(function ($r) {
        return [
            'id' => $r->ID,
            'name' => trim($r->first_name . ' ' . $r->last_name),
            'avs' => $r->avs_number
        ];
    }, $results);

    wp_send_json_success(['results' => $formatted]);
}






function enqueue_account_dashboard_style() {
    wp_enqueue_style( 'profile-css', plugins_url( '/css/profile.css', __FILE__ ), false, '1.0.0', 'all');
}
add_action( 'wp_enqueue_scripts', 'enqueue_account_dashboard_style', 1);




function tutors_profile_page_shortcode($atts) {
    wp_enqueue_style('profile-page-css', plugins_url('/css/profile-page.css', __FILE__));
    
    $atts = shortcode_atts([
        'user_id' => get_current_user_id(),
    ], $atts);

    $user_id = intval($atts['user_id']);
    $user = get_user_by('id', $user_id);

    if (!$user || !in_array('tutor', $user->roles)) {
        return '<p>Tutor not found.</p>';
    }

    $current_user_id = get_current_user_id();
    $is_editable = ($current_user_id === $user_id);

    $name = esc_html($user->display_name);
    $avatar = get_user_meta($user_id, 'custom_profile_avatar', true) ?: get_avatar_url($user_id);
    $subjects = get_user_meta($user_id, 'subjects_handled', true);
    $video_url = get_user_meta($user_id, 'video_intro_url', true);

    // Handle profile updates
    if ($is_editable && isset($_POST['save_tutor_profile']) && check_admin_referer('update_tutor_profile')) {
        if (!empty($_POST['video_intro_url'])) {
            update_user_meta($user_id, 'video_intro_url', sanitize_text_field($_POST['video_intro_url']));
        }

        if (!empty($_POST['subjects_handled'])) {
            update_user_meta($user_id, 'subjects_handled', sanitize_text_field($_POST['subjects_handled']));
        }

        if (!function_exists('media_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        // Handle avatar upload
        if (!empty($_FILES['profile_avatar']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            $uploaded = media_handle_upload('profile_avatar', 0);

            if (!is_wp_error($uploaded)) {
                update_user_meta($user_id, 'wp_user_avatar', $uploaded); // if using WP User Avatar plugin
                update_user_meta($user_id, 'custom_profile_avatar', wp_get_attachment_url($uploaded)); // fallback
            }
        }

        echo '<p style="color:green;">Profile updated successfully.</p>';
        $avatar = get_avatar_url($user_id); // refresh avatar
        $subjects = get_user_meta($user_id, 'subjects_handled', true);
        $video_url = get_user_meta($user_id, 'video_intro_url', true);
    }


    ob_start();

    echo '<div class="tutor-profile">';
    echo '<img src="' . esc_url($avatar) . '" class="tutor-profile-photo" />';
    echo '<h2>' . $name . '</h2>';

    if ($is_editable) {
        echo '<form method="post" enctype="multipart/form-data">';
        wp_nonce_field('update_tutor_profile');

        // Avatar upload
        echo '<p><label>Change Profile Picture:</label><br>';
        echo '<input type="file" name="profile_avatar" accept="image/*" /></p>';

        // Subjects
        echo '<p><label>Subjects (comma-separated):</label><br>';
        echo '<input type="text" name="subjects_handled" value="' . esc_attr($subjects) . '" style="width:100%;max-width:500px;" /></p>';

        // Video URL
        echo '<p><label>Video Intro URL (YouTube, Vimeo, or MP4):</label><br>';
        echo '<input type="url" name="video_intro_url" value="' . esc_attr($video_url) . '" style="width:100%;max-width:500px;" /></p>';

        echo '<input type="submit" name="save_tutor_profile" value="Save Changes" />';
        echo '</form>';
    } else {
        echo '<p><strong>Subjects:</strong> ' . esc_html($subjects ?: 'N/A') . '</p>';
        echo '<p><strong>Video:</strong> ' . ($video_url ? '<a href="' . esc_url($video_url) . '" target="_blank">Watch Intro</a>' : 'No video provided.') . '</p>';
    }

    echo '</div>';

    return ob_get_clean();
}

add_shortcode('tutor_profile_page', 'tutors_profile_page_shortcode');