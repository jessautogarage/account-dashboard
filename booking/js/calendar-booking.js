document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar-booking-calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        selectable: true,
        selectMirror: true,
        navLinks: true,
        editable: false,
        dayMaxEvents: true,
        events: {
            url: calendarBooking.ajaxurl,
            method: 'POST',
            extraParams: {
                action: 'get_bookings',
                nonce: calendarBooking.nonce
            }
        },
        select: function (info) {
            document.getElementById('booking-date').value = info.startStr.split('T')[0];
            document.getElementById('booking-start-time').value = info.startStr;
            document.getElementById('booking-end-time').value = info.endStr;
            document.getElementById('booking-form').style.display = 'block';

            calendar.unselect();
        }
    });

    calendar.render();

    document.getElementById('calendar-booking-form').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('action', 'create_booking');
        formData.append('nonce', calendarBooking.nonce);

        fetch(calendarBooking.ajaxurl, {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Booking created successfully!');
                    document.getElementById('booking-form').style.display = 'none';
                    calendar.refetchEvents();
                } else {
                    alert('Error: ' + data.message);
                }
            });
    });

    document.getElementById('cancel-booking').addEventListener('click', function () {
        document.getElementById('booking-form').style.display = 'none';
    });

    attachBookingListeners(); // Initial manual slot buttons
});

function fetchUpdatedSlots(tutor, date) {
    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'get_tutor_slots',
            tutor: tutor,
            date: date
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const container = document.querySelector('#slots-container');
                container.innerHTML = ''; // Clear current slots

                data.slots.forEach(slot => {
                    const btn = document.createElement('button');
                    btn.className = 'book-slot';
                    btn.dataset.date = date;
                    btn.dataset.time = slot.start_time;
                    btn.dataset.tutor = tutor;
                    btn.dataset.scheduleId = slot.id;

                    btn.textContent = `${slot.start_time} - ${slot.end_time}`;
                    btn.disabled = slot.is_booked == 1;

                    container.appendChild(btn);

                    if (slot.is_booked == 1) {
                        const cancelBtn = document.createElement('button');
                        cancelBtn.className = 'cancel-slot';
                        cancelBtn.textContent = 'Cancel';
                        cancelBtn.dataset.scheduleId = slot.id;
                        cancelBtn.addEventListener('click', () => {
                            if (confirm('Cancel this booking?')) {
                                cancelBooking(slot.id, tutor, date);
                            }
                        });
                        container.appendChild(cancelBtn);
                    }
                });

                attachBookingListeners();
            }
        });
}

function attachBookingListeners() {
    document.querySelectorAll(".book-slot").forEach(btn => {
        btn.addEventListener("click", function () {
            const date = this.dataset.date;
            const time = this.dataset.time;
            const tutor = this.dataset.tutor;

            if (confirm(`Book a session with tutor ${tutor} on ${date} at ${time}?`)) {
                fetch('/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'book_tutor_slot',
                        date: date,
                        time: time,
                        tutor: tutor
                    })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert('Booking confirmed!');
                            fetchUpdatedSlots(tutor, date);
                        } else {
                            alert('Booking failed: ' + data.message);
                        }
                    });
            }
        });
    });

    document.querySelectorAll(".cancel-slot").forEach(btn => {
        btn.addEventListener("click", function () {
            const scheduleId = this.dataset.scheduleId;
            const tutor = this.dataset.tutor;
            const date = this.dataset.date;

            if (confirm("Are you sure you want to cancel this booking?")) {
                cancelBooking(scheduleId, tutor, date);
            }
        });
    });
}

function cancelBooking(scheduleId, tutor, date) {
    fetch('/wp-admin/admin-ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'cancel_tutor_slot',
            schedule_id: scheduleId
        })
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Booking canceled.');
                fetchUpdatedSlots(tutor, date);
            } else {
                alert('Cancellation failed: ' + data.message);
            }
        });
}
