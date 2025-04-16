<?php
if(!defined('ABSPATH')){
    exit; // Exit if accessed directly
}

function user_lost_password_form_shortcode() {
    ob_start();
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
        .custom-login-form input[type="email"],
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

        <form action="<?php echo esc_url(wp_lostpassword_url()); ?>" method="post">
            <label for="user_login">Username or Email</label>
            <input type="text" id="user_login" name="user_login" placeholder="Username or Email" required>

            <input type="submit" value="Reset Password">

            <?php wp_nonce_field('user_lost_password_action', 'user_lost_password_nonce'); ?>

            <div class="login-link">
                <a href="<?php echo esc_url(site_url('/')); ?>">← Back to Login</a>
            </div>
        </form>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('user_lost_password_form', 'user_lost_password_form_shortcode');
