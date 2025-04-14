
document.addEventListener('DOMContentLoaded', function () {
    const showBtn = document.getElementById('show-calendar-btn');
    const formContainer = document.getElementById('availability-form');
    const selectedDateHeading = document.getElementById('selected-date-heading');
    const timeSlotContainer = document.getElementById('time-slots');
    const submitBtn = document.getElementById('submit-availability');

    let selectedDay = '';
    let selectedDateStr = '';
    let selectedTimeSlots = [];

    // Show calendar form
    showBtn.addEventListener('click', function () {
        formContainer.style.display = 'block';
    });

    // Initialize calendar
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        dateClick: function (info) {
            selectedDateStr = info.dateStr;
            const selectedDate = new Date(info.dateStr);
            selectedDay = selectedDate.toLocaleString('en-us', { weekday: 'long' });

            selectedDateHeading.innerHTML = `<h3>Selected Date: ${selectedDateStr} (${selectedDay})</h3>`;
            loadTimeSlots();
        }
    });
    calendar.render();

    // Load hourly slots from 8am to 6pm editable
    function loadTimeSlots() {
        let startHour = 8;
        let endHour = 18;
        let output = '';
        selectedTimeSlots = [];

        for (let h = startHour; h < endHour; h++) {
            const slot1 = `${String(h).padStart(2, '0')}:00`;
            const slot2 = `${String(h).padStart(2, '0')}:30`;

            output += `<button class="slot-btn" data-time="${slot1}">${slot1}</button>`;
            output += `<button class="slot-btn" data-time="${slot2}">${slot2}</button>`;
        }

        timeSlotContainer.innerHTML = output;
        submitBtn.style.display = 'inline-block';

        // Slot button selection
        document.querySelectorAll('.slot-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const time = this.dataset.time;
                this.classList.toggle('selected');

                if (selectedTimeSlots.includes(time)) {
                    selectedTimeSlots = selectedTimeSlots.filter(t => t !== time);
                } else {
                    selectedTimeSlots.push(time);
                }
            });
        });
    }

    formContainer.style.display = 'none';

    // Submit selected slots
    submitBtn.addEventListener('click', function () {
        if (!selectedDateStr || selectedTimeSlots.length === 0) {
            alert('Please select at least one time slot.');
            return;
        }

        // Prepare data to send
        const data = new URLSearchParams({
            action: 'save_multiple_tutor_slots', // ✅ Match the action name used in PHP
            nonce: availability_ajax_obj.nonce,  // ✅ Add nonce if you're verifying it
            date: selectedDateStr,
            time_slots: JSON.stringify(selectedTimeSlots)
        });

        fetch(availability_ajax_obj.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: data
        })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    alert(response.data); // ✅ Display success message
                    // Reset UI
                    selectedTimeSlots = [];
                    timeSlotContainer.innerHTML = '';
                    selectedDateHeading.innerHTML = '';
                    formContainer.style.display = 'none';
                } else {
                    alert('Error: ' + response.data);
                }
            })
            .catch(err => {
                console.error('AJAX error:', err);
                alert('An error occurred while saving availability.');
            });
    });
});