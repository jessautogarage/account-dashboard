<?php
if(!defined('ABSPATH')){
    exit();
}

function show_tutors_profile_shortcode(){
    $current_user = wp_get_current_user();
    $roles = array_map('ucfirst',$current_user->roles);
    $user_id = $current_user->ID;
    $custom_default = esc_url( get_site_url() . '/wp-content/uploads/2025/03/user.png' );
    $avatar = get_avatar_url($user_id) !== get_option('avatar_default') ? get_avatar_url($user_id) : $custom_default;
    ob_start();
    ?>
    
    <div class="profile-card">
        <!-- Profile Picture -->
        <img src="<?php echo $avatar;?>" class="profile-image"/>
        <!-- Profile Info -->
        <div class="profile-info">
            <p class="greeting">Good day, <?php echo implode(', ', $roles);?> <?php echo ucfirst($current_user->display_name);?></p><span><em><a href="#">edit</a></em></span>
            <p><strong>Favorite:</strong> <span>28</span></p>
            <p><strong>Followers:</strong> <span>28</span></p>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div>
                <span class="badge">K-12 Teacher - HS Teacher</span>
            </div>
            <div>
                <span>Evaluated Lessons</span>
                <p><strong>12</strong></p>
            </div>
            <div>
                <span>Satisfaction Rate</span>
                <p><strong>95%</strong> ⭐</p>
            </div>
        </div>

        <!-- Buttons -->
        <div class="buttons">
            <button class="btn btn-yellow">Contact Admin</button>
            <button class="btn btn-dark">My Classroom</button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('show_tutors_profile', 'show_tutors_profile_shortcode');

function show_student_profile_shortcode(){
    $current_user = wp_get_current_user();
    $roles = array_map('ucfirst',$current_user->roles);
    $user_id = $current_user->ID;
    $custom_default = esc_url( get_site_url() . '/wp-content/uploads/2025/03/user.png' );
    $avatar = get_avatar_url($user_id) !== get_option('avatar_default') ? get_avatar_url($user_id) : $custom_default;
    ob_start();
    ?>
    
    <div class="profile-card">
        <!-- Profile Picture -->
        <img src="<?php echo $avatar;?>" class="profile-image"/>
        <!-- Profile Info -->
        <div class="profile-info">
            <p class="greeting">Good day, <?php echo implode(', ', $roles);?> <?php echo ucfirst($current_user->display_name);?></p><span><em><a href="#">edit</a></em></span>
            <p><strong>Favorite:</strong> <span>28</span></p>
            <p><strong>Followers:</strong> <span>28</span></p>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div>
                <span class="badge">Grade 12 Student</span>
            </div>
            <div>
                <span>Evaluated Lessons</span>
                <p><strong>12</strong></p>
            </div>
            <div>
                <span>Satisfaction Rate</span>
                <p><strong>95%</strong> ⭐</p>
            </div>
        </div>

        <!-- Buttons -->
        <div class="buttons">
            <button class="btn btn-yellow">Contact Admin</button>
            <button class="btn btn-dark">My Classroom</button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('show_student_profile', 'show_student_profile_shortcode');