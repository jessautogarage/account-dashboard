<?php
if(!defined('ABSPATH')){
    exit();
}


function show_time_availability_shortcodes(){
    ob_start();
    ?>
        <div class="availability-container">
            <h2 class="availability-title">My Availability</h2>

            <div class="availability-info">
                <span>Opened Slots: <span class="highlight">62</span></span>
                <span>Potential Earning: <span class="highlight currency">₱ 3348</span></span>
                <button class="submit-btn">SUBMIT</button>
            </div>

            <div class="navigation">
                <a href="#" class="nav-link"><< Pre</a>
                <a href="#" class="nav-link">Next >></a>
            </div>

            <div class="availability-table-container">
                <table class="availability-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>01-01<br>Mon</th>
                            <th>01-02<br>Tue</th>
                            <th>01-03<br>Wed</th>
                            <th>01-04<br>Thur</th>
                            <th>01-05<br>Fri</th>
                            <th>01-06<br>Sat</th>
                            <th>01-07<br>Sun</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Generate multiple rows dynamically -->
                        <script>
                            let times = ["00:00", "00:30", "01:00", "01:30", "02:00", "02:30", "03:00", "03:30",
                                        "04:00", "04:30", "05:00", "05:30", "06:00", "06:30", "07:00", "07:30"];
                            let tableBody = '';

                            times.forEach(time => {
                                tableBody += `<tr><td>${time}</td>`;
                                for (let i = 0; i < 7; i++) {
                                    tableBody += `<td class="closed">Closed</td>`;
                                }
                                tableBody += `</tr>`;
                            });

                            document.write(tableBody);
                        </script>
                    </tbody>
                </table>
            </div>
        </div>
    <?php
    return ob_get_clean();
}

add_shortcode('time_availability', 'show_time_availability_shortcodes');