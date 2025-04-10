<?php
if(!defined('ABSPATH')){
exit();
}                                                                                                        

function user_login_form_shortcode(){
    ob_start();
    ?>
    <div class="user-login-form">
        <h2>Login</h2>
        <form action="<?php echo esc_url(wp_login_url()); ?>" method="post">
            <label for="username">Username</label>
            <input type="text" id="username" name="log" placeholder="Username" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="pwd" placeholder="Password" required>

            <!-- Add Nonce for Security -->
            <?php wp_nonce_field('user_login_action', 'user_login_nonce'); ?>

            <input type="submit" value="Login" class="btn btn-primary">
            
            <p><a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Lost Password?</a></p>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('user_login_form', 'user_login_form_shortcode');
