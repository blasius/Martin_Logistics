<x-filament::section>
    <div id="calendar"></div>

    @vite(['resources/css/app.css','resources/js/app.js'])

    <script type="module">
        import { Calendar } from 'fullcalendar';

        document.addEventListener('DOMContentLoaded', function () {
            const calendarEl = document.getElementById('calendar');

            const calendar = new Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: @json($events),
                eventClick: function(info) {
                    if (info.event.url) {
                        window.open(info.event.url, '_blank');
                        info.jsEvent.preventDefault();
                    }
                }
            });

            calendar.render();
        });
    </script>

    <style>
        #calendar {
            max-width: 100%;
            margin: 20px auto;
            background: white;
            border-radius: 12px;
            padding: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
    </style>
</x-filament::section>
