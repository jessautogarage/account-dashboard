<?php
if(!defined('ABSPATH')){
exit;
}                                                                                                        

function user_login_form_shortcode() {
    ob_start();

    // Check for login failure
    $login_failed = isset($_GET['login']) && $_GET['login'] === 'failed';
    ?>

    <style>
        .custom-login-form {
            width: 100%;
            max-width: 500px;
            margin: 40px auto;
            padding: 20px;
            
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            background: #f6faec;
        }


        .custom-login-form input[type="text"],
        .custom-login-form input[type="password"],
        .custom-login-form input[type="submit"] {
            width: 100%;
            padding: 10px 15px;
            margin-bottom: 15px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .custom-login-form input[type="submit"] {
            background-color: #3d4b1f;
            border-color: #3d4b1f;
            color: white;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .custom-login-form input[type="submit"]:hover {
            background-color: #344218;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle span {
            position: absolute;
            right: 10px;
            top: 40%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 6px;
        }

        .login-link {
            display: block;
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }

        .login-link a {
            color: #3d4b1f;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>

    <div class="custom-login-form">
        <div class="login-logo" style="text-align: center; margin-bottom: 20px;">
            <img src="https://tutors.avocadova.com/wp-content/uploads/2025/03/With-BG-1-1-1.jpg" 
                alt="Site Logo" 
                style="max-width: 200px; height: auto;">
        </div>


        <?php if ($login_failed): ?>
            <div class="alert-error">
                ❌ Invalid username or password. Please try again.
            </div>
        <?php endif; ?>

        <form action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>" method="post">
            <label for="username">Username</label>
            <input type="text" id="username" name="log" placeholder="Username" required>

            <label for="password">Password</label>
            <div class="password-toggle">
                <input type="password" id="password" name="pwd" placeholder="Password" required>
                <span onclick="togglePassword('password')">👁️</span>
            </div>

            <input type="submit" value="Login">

            <input type="hidden" name="redirect_to" value="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" />
            <?php wp_nonce_field('user_login_action', 'user_login_nonce'); ?>

            <div class="login-link">
                <a href="<?php echo esc_url(site_url('/lost-password')); ?>">Lost Password?</a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            input.type = input.type === 'password' ? 'text' : 'password';
        }
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('user_login_form', 'user_login_form_shortcode');


 
function custom_login_failed_redirect() {
    $referrer = wp_get_referer();
    if (!empty($referrer) && !str_contains($referrer, 'wp-login') && !is_user_logged_in()) {
        wp_redirect(add_query_arg('login', 'failed', $referrer));
        exit;  
    }
}
add_action('wp_login_failed', 'custom_login_failed_redirect');
