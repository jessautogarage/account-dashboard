<?php
if(!defined('ABSPATH')) exit; // Exit if accessed directly

// Get Zoom OAuth Access Token
function get_zoom_access_token() {
    $client_id = 'nVVwaw45Skmv7mJqtJjGxQ'; // Replace with your Zoom Client ID
    $client_secret = 'BQIQM8cw1aNDNbSxP92p9yJJihCuiOuu'; // Replace with your Zoom Client Secret
    $account_id = 'rCXZA9K1QRCoEUMaHCG1yA'; // Replace with your Zoom Account ID   5AvNBoWKTBeNea-eC7zShA

    $token_url = 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id=' . $account_id;

    $headers = [
        'Authorization: Basic ' . base64_encode($client_id . ':' . $client_secret)
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    error_log("🧪 Zoom Access Token Response: $response");

    $token = json_decode($response, true);

    if (isset($token['access_token'])) {
        return $token['access_token'];
    } else {
        error_log('⚠️ Failed to get access token: ' . ($token['message'] ?? 'Unknown error'));
        return null;
    }
}



// Display the Form and Handle Submission
function zoom_join_form_shortcode() {
    ob_start(); ?>

    <div id="zmmtg-root"></div>
    <div id="aria-notify-area"></div>

    <form id="zoom-join-form" method="post">
        <p>
            <label>Your Name: <input type="text" name="display_name" required></label>
        </p>
        <p>
            <input type="submit" name="join_meeting" value="Join Zoom Meeting">
        </p>
    </form>

    <?php if (isset($_POST['join_meeting'])): ?>
        <script src="https://source.zoom.us/3.1.0/lib/vendor/react.min.js"></script>
        <script src="https://source.zoom.us/3.1.0/lib/vendor/react-dom.min.js"></script>
        <script src="https://source.zoom.us/3.1.0/lib/vendor/redux.min.js"></script>
        <script src="https://source.zoom.us/3.1.0/lib/vendor/redux-thunk.min.js"></script>
        <script src="https://source.zoom.us/3.1.0/lib/vendor/lodash.min.js"></script>
        <script src="https://source.zoom.us/zoom-meeting-3.1.0.min.js"></script>
        <link rel="stylesheet" href="https://source.zoom.us/3.1.0/css/bootstrap.css" />
        <link rel="stylesheet" href="https://source.zoom.us/3.1.0/css/react-select.css" />

        <script>
        const meetingNumber = 'YOUR_MEETING_ID'; // Replace with actual meeting ID
        const userName = '<?php echo esc_js($_POST['display_name']); ?>';
        const apiKey = 'YOUR_ZOOM_SDK_API_KEY';
        const signature = 'YOUR_GENERATED_SIGNATURE'; // Must be role 0 (attendee)
        const leaveUrl = 'https://yourdomain.com/thank-you';

        ZoomMtg.setZoomJSLib('https://source.zoom.us/3.1.0/lib', '/av');

        ZoomMtg.init({
        leaveUrl: 'https://yourdomain.com/thank-you',
        success: function () {
            ZoomMtg.join({
            apiKey: 'YOUR_API_KEY',
            signature: 'YOUR_SIGNATURE',
            meetingNumber: 'YOUR_MEETING_ID',
            userName: 'Guest User',
            passWord: '',
            success: function () {
                console.log('✅ Joined Zoom meeting!');
            },
            error: function (err) {
                console.error('Zoom join error:', err);
            }
            });
        },
        error: function (err) {
            console.error('Zoom init error:', err);
        }
        });
        </script>
    <?php endif;

    return ob_get_clean();
}
add_shortcode('zoom_join_form', 'zoom_join_form_shortcode');


