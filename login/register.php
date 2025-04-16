<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Register custom tables for student, parent, and tutor
function student_registration_shortcode() {
    wp_enqueue_style(
        'student-registration-css',
        plugins_url('/css/register.css', __FILE__),
        array(),
        '1.0.0',
        'all'
    );
    ob_start();

    // Handle form submission.
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['custom_user_registration'] ) ) {

        if ( ! isset( $_POST['custom_user_registration_nonce'] ) || ! wp_verify_nonce( $_POST['custom_user_registration_nonce'], 'custom_user_registration' ) ) {
            echo '<p>Error: Security check failed.</p>';
            return ob_get_clean();
        }
    
        // Sanitize and validate input
        $username    = sanitize_user( $_POST['user_login'] );
        $email       = sanitize_email( $_POST['user_email'] );
        $password    = $_POST['user_pass'];

        $first_name  = ucwords(strtolower(trim(sanitize_text_field($_POST['first_name']))));
        $last_name   = ucwords(strtolower(trim(sanitize_text_field($_POST['last_name']))));
        $subject     = ucwords(strtolower(trim(sanitize_text_field($_POST['subject']))));
        $grade_level = ucwords(strtolower(trim(sanitize_text_field($_POST['grade_level']))));
        $school      = ucwords(strtolower(trim(sanitize_text_field($_POST['school']))));

    
        $errors = new WP_Error();
    
        // Validate fields
        if ( empty( $username ) ) {
            $errors->add( 'empty_username', 'Username is required.' );
        } elseif ( username_exists( $username ) ) {
            $errors->add( 'username_exists', 'Username already exists.' );
        }
    
        if ( empty( $email ) ) {
            $errors->add( 'empty_email', 'Email is required.' );
        } elseif ( ! is_email( $email ) ) {
            $errors->add( 'invalid_email', 'Invalid email address.' );
        } elseif ( email_exists( $email ) ) {
            $errors->add( 'email_exists', 'Email already in use.' );
        }
    
        if ( empty( $password ) ) {
            $errors->add( 'empty_password', 'Password is required.' );
        }
    
        // If no errors, create user and save meta
        if ( empty( $errors->errors ) ) {
            $user_id = wp_create_user( $username, $password, $email );
            if ( ! is_wp_error( $user_id ) ) {
    
                // Set role to student
                $user = new WP_User( $user_id );
                $user->set_role( 'student' );
    
                // Save to usermeta
                update_user_meta( $user_id, 'first_name', $first_name );
                update_user_meta( $user_id, 'last_name', $last_name );

                // AVS Number Generation
                $first_initial = strtoupper(substr($first_name, 0, 1));
                $last_initial  = strtoupper(substr($last_name, 0, 1));
                $month         = date('m');
                $day           = date('d');

                // Prefix: AB0411 (if name is Alice Brown on April 11)
                $prefix = $first_initial . $last_initial . $month . $day;

                // Find latest AVS for this prefix
                global $wpdb;
                $last_avs_number = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT avs_number FROM {$wpdb->prefix}student 
                        WHERE avs_number LIKE %s 
                        ORDER BY avs_number DESC LIMIT 1",
                        $wpdb->esc_like($prefix) . '%'
                    )
                );

                // Calculate next incremental number
                if ( $last_avs_number ) {
                    $last_increment = (int) substr($last_avs_number, -4);
                    $next_increment = str_pad($last_increment + 1, 4, '0', STR_PAD_LEFT);
                } else {
                    $next_increment = '0001';
                }

                $avs_number = $prefix . $next_increment;

    
                // Save to wp_student
                $wpdb->insert(
                    $wpdb->prefix . 'student',
                    array(
                        'user_id'     => $user_id,
                        'subject'     => $subject,
                        'grade_level' => $grade_level,
                        'school'      => $school,
                        'avs_number'  => $avs_number,
                    ),
                    array( '%d', '%s', '%s', '%s', '%s' )
                );
                // Send welcome email
                avocadova_send_welcome_email( $user_id );
    
                // Redirect to avoid resubmission
                $redirect_url = add_query_arg( 'reg_success', '1', get_permalink() );
                if ( ! headers_sent() ) {
                    wp_redirect( $redirect_url );
                    exit;
                } else {
                    echo '<script>window.location.href="' . esc_url( $redirect_url ) . '";</script>';
                    exit;
                }
    
            } else {
                echo '<p>Error: ' . esc_html( $user_id->get_error_message() ) . '</p>';
            }
        } else {
            foreach ( $errors->get_error_messages() as $error ) {
                echo '<p>Error: ' . esc_html( $error ) . '</p>';
            }
        }
    }
    
    ?>

        <form method="post" id="custom-registration-form" style="max-width: 400px; margin: auto;">
            <?php wp_nonce_field( 'custom_user_registration', 'custom_user_registration_nonce' ); ?>

            <label for="first_name">Name</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" id="first_name" name="first_name" placeholder="First Name" required style="flex: 1;">
                <input type="text" id="last_name" name="last_name" placeholder="Surname" required style="flex: 1;">
            </div>

            <label for="user_login">Username</label>
            <input type="text" id="user_login" name="user_login" placeholder="Your username" required>

            <label for="user_email">E-Mail Address</label>
            <input type="email" id="user_email" name="user_email" placeholder="john.doe@gmail.com" required>

            <label for="user_pass">Password</label>
            <div style="position: relative;">
                <input type="password" id="user_pass" name="user_pass" placeholder="********" required>
                <span onclick="togglePassword('user_pass')" style="position:absolute; right:10px; top:50%; transform: translateY(-50%); cursor:pointer;">👁️</span>
            </div>

            <label for="user_pass_repeat">Repeat Password</label>
            <div style="position: relative;">
                <input type="password" id="user_pass_repeat" name="user_pass_repeat" placeholder="********" required>
                <span onclick="togglePassword('user_pass_repeat')" style="position:absolute; right:10px; top:50%; transform: translateY(-50%); cursor:pointer;">👁️</span>
            </div>

            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" placeholder="Math, Science, etc.">
                </div>
                <div style="flex: 1;">
                <label for="grade_level">Grade Level</label>
                <select id="grade_level" name="grade_level" required>
                    <option value="" disabled selected>Select options</option>
                    <option value="Kindergarten">Kindergarten</option>
                    <option value="1st Grade">1st Grade</option>
                    <option value="2nd Grade">2nd Grade</option>
                    <option value="3rd Grade">3rd Grade</option>
                    <option value="4th Grade">4th Grade</option>
                    <option value="5th Grade">5th Grade</option>
                    <option value="6th Grade">6th Grade</option>
                    <option value="7th Grade">7th Grade</option>
                    <option value="8th Grade">8th Grade</option>
                    <option value="9th Grade">9th Grade</option>
                    <option value="10th Grade">10th Grade</option>
                    <option value="11th Grade">11th Grade</option>
                    <option value="12th Grade">12th Grade</option>
                    <option value="Tertiary">Tertiary</option>
                    <option value="Working Professional">Working Professional</option>
                </select>


                </div>
            </div>


            <label for="school">School</label>
            <input type="text" id="school" name="school" placeholder="West Academy">

            <input type="submit" name="custom_user_registration" value="Sign-up" id="register-button">
        </form>



        <!-- Fullscreen Spinner Overlay -->
        <div id="spinner-overlay" style="display:none;">
            <div class="spinner-content" id="spinner-message-box">
                <div class="loader" id="spinner-loader"></div>
                <p id="spinner-message">Please wait…</p>
            </div>
        </div>

        <!-- Spinner Script -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('custom-registration-form');
                const spinnerOverlay = document.getElementById('spinner-overlay');
                const submitBtn = document.getElementById('register-button');
                const spinnerMessage = document.getElementById('spinner-message');
                const spinnerLoader = document.getElementById('spinner-loader');

                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('reg_success') === '1') {
                    spinnerOverlay.style.display = 'flex';
                    spinnerLoader.style.display = 'none';
                    spinnerMessage.textContent = '✅ Registration successful!';
                    setTimeout(() => {
                        window.location.href = window.location.origin + '/'; // redirect to home
                    }, 2500);
                }
            });

            function togglePassword(inputId) {
                const input = document.getElementById(inputId);
                if (input.type === 'password') {
                    input.type = 'text';
                } else {
                    input.type = 'password';
                }
            }

        </script>

    <?php

    return ob_get_clean();
}
add_shortcode('student_registration_form', 'student_registration_shortcode');


// Parent Registration Form Shortcode
function parent_registration_shortcode() {
    wp_enqueue_style(
        'parent-registration-css',
        plugins_url('/css/parent-register.css', __FILE__),
        array(),
        '1.0.0',
        'all'
    );
    ob_start();

    // Handle form submission.
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['parent_user_registration'] ) ) {

        if ( ! isset( $_POST['custom_parent_registration_nonce'] ) || ! wp_verify_nonce( $_POST['custom_parent_registration_nonce'], 'custom_parent_registration' ) ) {
            echo '<p>Error: Security check failed.</p>';
            return ob_get_clean();
        }

        $first_name        = ucwords(strtolower(trim(sanitize_text_field($_POST['first_name']))));
        $last_name         = ucwords(strtolower(trim(sanitize_text_field($_POST['last_name']))));
        $email             = sanitize_email($_POST['user_email']);
        $mobile            = sanitize_text_field($_POST['mobile']);
        $password          = $_POST['user_pass'];
        $password_repeat   = $_POST['user_pass_repeat'];
        $child_first_name  = ucwords(strtolower(trim(sanitize_text_field($_POST['child_first_name']))));
        $child_last_name   = ucwords(strtolower(trim(sanitize_text_field($_POST['child_last_name']))));
        $avs_number        = sanitize_text_field( $_POST['avs_number'] );
    
        $username = sanitize_user( explode('@', $email)[0] ); // default username from email
        $errors = new WP_Error();
    
        // Validations
        if ( empty( $first_name ) || empty( $last_name ) ) {
            $errors->add( 'name_required', 'Parent name is required.' );
        }
    
        if ( empty( $email ) || ! is_email( $email ) ) {
            $errors->add( 'invalid_email', 'Valid email is required.' );
        } elseif ( email_exists( $email ) ) {
            $errors->add( 'email_exists', 'Email already in use.' );
        }
    
        if ( empty( $password ) || empty( $password_repeat ) ) {
            $errors->add( 'empty_password', 'Both password fields are required.' );
        } elseif ( $password !== $password_repeat ) {
            $errors->add( 'password_mismatch', 'Passwords do not match.' );
        }
    
        if ( empty( $errors->errors ) ) {
            $user_id = wp_create_user( $username, $password, $email );
            if ( ! is_wp_error( $user_id ) ) {
    
                // Set parent role
                $user = new WP_User( $user_id );
                $user->set_role( 'parent' );
    
                // Save to wp_usermeta
                update_user_meta( $user_id, 'first_name', $first_name );
                update_user_meta( $user_id, 'last_name', $last_name );
                if ( $mobile ) {
                    update_user_meta( $user_id, 'mobile', $mobile );
                }
    
                // Save to wp_parent custom table
                global $wpdb;

                // Look up student by AVS number
                $student_user_id = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT user_id FROM {$wpdb->prefix}student WHERE avs_number = %s",
                        $avs_number
                    )
                );

                if ( $student_user_id ) {
                    // Insert into wp_parent table
                    $wpdb->insert(
                        $wpdb->prefix . 'parent',
                        array(
                            'user_id'           => $user_id, // parent’s ID
                            'mobile'            => $mobile,
                            'child_first_name'  => $child_first_name,
                            'child_last_name'   => $child_last_name,
                            'avs_number'        => $avs_number,
                            'student_id'     => $student_user_id // OPTIONAL: add this column to track relationship
                        ),
                        array( '%d', '%s', '%s', '%s', '%s', '%d' )
                    );
                } else {
                    echo '<p style="color:red;">Error: AVS number not found. Please check and try again.</p>';
                }

    
                // Redirect to avoid resubmission
                $redirect_url = add_query_arg( 'reg_success', '1', get_permalink() );
                if ( ! headers_sent() ) {
                    wp_redirect( $redirect_url );
                    exit;
                } else {
                    echo '<script>window.location.href="' . esc_url( $redirect_url ) . '";</script>';
                    exit;
                }
    
            } else {
                echo '<p>Error: ' . esc_html( $user_id->get_error_message() ) . '</p>';
            }
        } else {
            foreach ( $errors->get_error_messages() as $error ) {
                echo '<p style="color:red;">Error: ' . esc_html( $error ) . '</p>';
            }
        }
    }
    
    
    ?>

    <form method="post" id="parent-registration-form" style="max-width: 400px; margin: auto;">
        <?php wp_nonce_field( 'custom_parent_registration', 'custom_parent_registration_nonce' ); ?>

        <!-- Parent Info -->
        <label for="first_name">Name</label>
        <div style="display: flex; gap: 10px;">
            <input type="text" id="first_name" name="first_name" placeholder="First Name" required style="flex: 1;">
            <input type="text" id="last_name" name="last_name" placeholder="Surname" required style="flex: 1;">
        </div>

        <label for="user_email">E-Mail Address</label>
        <input type="email" id="user_email" name="user_email" placeholder="john.doe@gmail.com" required>

        <label for="mobile">Mobile Number (optional)</label>
        <input type="text" id="mobile" name="mobile" placeholder="123-2345-6789">

        <label for="user_pass">Password</label>
        <div style="position: relative;">
            <input type="password" id="user_pass" name="user_pass" placeholder="********" required>
            <span onclick="togglePassword('user_pass')" style="position:absolute; right:10px; top:50%; transform: translateY(-50%); cursor:pointer;">👁️</span>
        </div>

        <label for="user_pass_repeat">Repeat Password</label>
        <div style="position: relative;">
            <input type="password" id="user_pass_repeat" name="user_pass_repeat" placeholder="********" required>
            <span onclick="togglePassword('user_pass_repeat')" style="position:absolute; right:10px; top:50%; transform: translateY(-50%); cursor:pointer;">👁️</span>
        </div>

        <!-- Divider -->
        <h3 style="text-align:center; color:#4c5c2c; font-size: 16px; margin-top: 20px;">Already have a registered child?</h3>

        <label for="avs_number">Avocado Student Number (AVS_Number)</label>
        <input type="text" class="form-control is-invalid" id="avs_number" name="avs_number" placeholder="AVS0123456">
        <div class="invalid-feedback" id="avs-error" style="display: none;"></div>

        <!-- Child Info -->
        <label for="child_name">Child’s Name</label>
        <div style="display: flex; gap: 10px;">
            <input type="text" id="child_first_name" name="child_first_name" placeholder="First Name" style="flex: 1;" readonly>
            <input type="text" id="child_last_name" name="child_last_name" placeholder="Surname" style="flex: 1;" readonly>
        </div>

        <input type="submit" name="parent_user_registration" value="Sign-up" id="register-button">
    </form>





    <!-- Fullscreen Spinner Overlay -->
    <div id="spinner-overlay" style="display:none;">
        <div class="spinner-content" id="spinner-message-box">
            <div class="loader" id="spinner-loader"></div>
            <p id="spinner-message">Please wait…</p>
        </div>
    </div>

    <!-- Spinner Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
        const spinnerOverlay = document.getElementById('spinner-overlay');
        const spinnerMessage = document.getElementById('spinner-message');
        const spinnerLoader = document.getElementById('spinner-loader');

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('reg_success') === '1') {
            spinnerOverlay.style.display = 'flex';
            spinnerLoader.style.display = 'none';
            spinnerMessage.textContent = '✅ Registration successful!';
            setTimeout(() => {
                window.location.href = window.location.origin + '/'; // Redirect to home
            }, 2500);
        }
    });

    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text';
        } else {
            input.type = 'password';
        }
    }

    document.getElementById('avs_number').addEventListener('blur', function () {
        const avsInput = this;
        const avsNumber = avsInput.value.trim();
        const errorBox = document.getElementById('avs-error');
        const firstNameInput = document.getElementById('child_first_name');
        const lastNameInput = document.getElementById('child_last_name');

        firstNameInput.value = '';
        lastNameInput.value = '';
        errorBox.style.display = 'none';
        errorBox.classList.remove('text-success');
        avsInput.classList.remove('is-invalid');

        if (avsNumber === '') return;

        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'check_avs_number',
                avs_number: avsNumber
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                firstNameInput.value = data.data.first_name ?? '';
                lastNameInput.value = data.data.last_name ?? '';
                errorBox.textContent = data.data.message;
                errorBox.style.display = 'block';
                errorBox.classList.add('text-success'); // ✅ green text
            } else {
                errorBox.textContent = data.data.message;
                errorBox.style.display = 'block';
                avsInput.classList.add('is-invalid');
            }
        })
        .catch(err => {
            errorBox.textContent = '⚠️ Something went wrong.';
            errorBox.style.display = 'block';
            avsInput.classList.add('is-invalid');
        });
    });


    </script>


    <?php

    return ob_get_clean();
}
add_shortcode('parent_registration_form', 'parent_registration_shortcode');


add_action('wp_ajax_check_avs_number', 'check_avs_number_ajax');
add_action('wp_ajax_nopriv_check_avs_number', 'check_avs_number_ajax');

function check_avs_number_ajax() {
    global $wpdb;

    $avs_number = sanitize_text_field($_POST['avs_number']);

    $student = $wpdb->get_row(
        $wpdb->prepare("SELECT user_id FROM {$wpdb->prefix}student WHERE avs_number = %s", $avs_number)
    );

    if ( $student ) {
        $user_id    = $student->user_id;
        $first_name = get_user_meta($user_id, 'first_name', true);
        $last_name  = get_user_meta($user_id, 'last_name', true);

        wp_send_json_success([
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'message'    => '✅ Student found. Thank you.'
        ]);
    } else {
        wp_send_json_error([
            'message' => '⚠️ No student found with that AVS Number.'
        ]);
    }

    wp_die();
}



// Tutor Registration Form Shortcode
function tutor_registration_shortcode() {
    wp_enqueue_style(
        'tutor-registration-css',
        plugins_url('/css/tutor-register.css', __FILE__),
        array(),
        '1.0.0',
        'all'
    );

    ob_start();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tutor_user_registration'])) {
        if (!isset($_POST['custom_tutor_registration_nonce']) || !wp_verify_nonce($_POST['custom_tutor_registration_nonce'], 'custom_tutor_registration')) {
            echo '<p>Error: Security check failed.</p>';
            return ob_get_clean();
        }

        $first_name      = ucwords(strtolower(trim(sanitize_text_field($_POST['first_name']))));
        $last_name       = ucwords(strtolower(trim(sanitize_text_field($_POST['last_name']))));
        $email           = sanitize_email($_POST['user_email']);
        $mobile          = sanitize_text_field($_POST['mobile']);
        $password        = $_POST['user_pass'];
        $password_repeat = $_POST['user_pass_repeat'];
        $username        = sanitize_user(explode('@', $email)[0]);
        $errors          = new WP_Error();


        if (empty($first_name) || empty($last_name)) {
            $errors->add('name_required', 'Name is required.');
        }

        if (empty($email) || !is_email($email)) {
            $errors->add('invalid_email', 'Valid email is required.');
        } elseif (email_exists($email)) {
            $errors->add('email_exists', 'Email already in use.');
        }

        if (empty($mobile)) {
            $errors->add('mobile_required', 'Mobile number is required.');
        }

        if (empty($password) || empty($password_repeat)) {
            $errors->add('empty_password', 'Password is required.');
        } elseif ($password !== $password_repeat) {
            $errors->add('password_mismatch', 'Passwords do not match.');
        }

        if (empty($errors->errors)) {
            $user_id = wp_create_user($username, $password, $email);
            if (!is_wp_error($user_id)) {
                $user = new WP_User($user_id);
                $user->set_role('tutor');

                update_user_meta($user_id, 'first_name', $first_name);
                update_user_meta($user_id, 'last_name', $last_name);
                update_user_meta($user_id, 'mobile', $mobile);

                global $wpdb;
                $wpdb->insert(
                    $wpdb->prefix . 'tutor',
                    array(
                        'user_id' => $user_id,
                        'mobile'  => $mobile,
                    ),
                    array('%d', '%s')
                );

                // Redirect to avoid resubmission
                $redirect_url = add_query_arg( 'reg_success', '1', get_permalink() );
                if ( ! headers_sent() ) {
                    wp_redirect( $redirect_url );
                    exit;
                } else {
                    echo '<script>window.location.href="' . esc_url( $redirect_url ) . '";</script>';
                    exit;
                }

            } else {
                echo '<p>Error: ' . esc_html($user_id->get_error_message()) . '</p>';
            }
        } else {
            foreach ($errors->get_error_messages() as $error) {
                echo '<p style="color:red;">Error: ' . esc_html($error) . '</p>';
            }
        }
    }
        ?>

        <form method="post" id="tutor-registration-form" style="max-width: 400px; margin: auto;">
            <?php wp_nonce_field('custom_tutor_registration', 'custom_tutor_registration_nonce'); ?>

            <label for="first_name">Name</label>
            <div style="display: flex; gap: 10px;">
                <input type="text" name="first_name" placeholder="First Name" required style="flex:1;">
                <input type="text" name="last_name" placeholder="Surname" required style="flex:1;">
            </div>

            <label for="user_email">E-Mail Address</label>
            <input type="email" name="user_email" placeholder="john.doe@gmail.com" required>

            <label for="mobile">Mobile Number</label>
            <input type="text" name="mobile" placeholder="091-2345-6789" required>

            <label for="user_pass">Password</label>
            <div style="position: relative;">
                <input type="password" name="user_pass" id="user_pass" placeholder="********" required>
                <span onclick="togglePassword('user_pass')" style="position:absolute; right:10px; top:50%; transform: translateY(-50%); cursor:pointer;">👁️</span>
            </div>

            <label for="user_pass_repeat">Repeat Password</label>
            <div style="position: relative;">
                <input type="password" name="user_pass_repeat" id="user_pass_repeat" placeholder="********" required>
                <span onclick="togglePassword('user_pass_repeat')" style="position:absolute; right:10px; top:50%; transform: translateY(-50%); cursor:pointer;">👁️</span>
            </div>

            <input type="submit" name="tutor_user_registration" value="Sign-up now!" id="register-button">
        </form>

        <div id="spinner-overlay" style="display:none;">
            <div class="spinner-content" id="spinner-message-box">
                <div class="loader" id="spinner-loader"></div>
                <p id="spinner-message">Please wait…</p>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const overlay = document.getElementById('spinner-overlay');
            const loader = document.getElementById('spinner-loader');
            const message = document.getElementById('spinner-message');

            const params = new URLSearchParams(window.location.search);
            if (params.get('reg_success') === '1') {
                overlay.style.display = 'flex';
                loader.style.display = 'none';
                message.textContent = '✅ Registration successful!';
                setTimeout(() => {
                    window.location.href = window.location.origin + '/';
                }, 2500);
            }
        });

        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            field.type = field.type === "password" ? "text" : "password";
        }
        </script>

        <?php
            return ob_get_clean();
}
add_shortcode('tutor_registration_form', 'tutor_registration_shortcode');
