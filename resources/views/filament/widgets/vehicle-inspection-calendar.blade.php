<div>
    {{-- container for the calendar (wire:ignore prevents Livewire from patching it) --}}
    <div wire:ignore id="vehicle-inspection-calendar" style="background:white;border-radius:8px;padding:12px;box-shadow:0 2px 8px rgba(0,0,0,0.04)"></div>

    {{-- load FullCalendar CSS & JS via CDN --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css"></script>

    <script>
        // events data injected from PHP
        const fcEvents = @json($events);

        function initInspectionCalendar() {
            const calendarEl = document.getElementById('vehicle-inspection-calendar');
            if (!calendarEl) return;

            // prevent duplicate initialization
            if (calendarEl.dataset.fcInitialized) return;
            calendarEl.dataset.fcInitialized = '1';

            // create FullCalendar instance (FullCalendar global available)
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: ''
                },
                events: fcEvents,
                eventClick: function(info) {
                    // if event has url, navigate in same tab (or open new)
                    if (info.event.extendedProps && info.event.extendedProps.url) {
                        window.location.href = info.event.extendedProps.url;
                        info.jsEvent.preventDefault();
                    } else if (info.event.url) {
                        window.location.href = info.event.url;
                        info.jsEvent.preventDefault();
                    }
                },
                height: 'auto'
            });

            calendar.render();
        }

        // initialize on page load
        document.addEventListener('DOMContentLoaded', function () {
            initInspectionCalendar();
        });

        // also initialize after Livewire navigation / updates
        document.addEventListener('livewire:load', function () {
            initInspectionCalendar();
        });
        // If Filament uses Turbo/Navigation events, also guard:
        document.addEventListener('turbo:load', function () {
            initInspectionCalendar();
        });
    </script>

    <style>
        /* small responsive fix */
        #vehicle-inspection-calendar .fc-daygrid-event {
            cursor: pointer;
        }
        /* color mapping — FullCalendar respects event.color, but ensure contrast */
    </style>
</div>
