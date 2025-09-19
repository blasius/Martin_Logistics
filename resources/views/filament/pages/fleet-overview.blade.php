<x-filament-panels::page>
    <div x-data="{ tab: 'table', filterText: '' }" class="space-y-4">

        <!-- Tabs -->
        <div class="flex border-b">
            <button class="px-4 py-2 text-sm font-medium"
                    :class="tab==='table'?'border-b-2 border-primary-600 text-primary-700':'text-gray-500'"
                    @click="tab='table'">Grouped by Region</button>
            <button class="px-4 py-2 text-sm font-medium"
                    :class="tab==='map'?'border-b-2 border-primary-600 text-primary-700':'text-gray-500'"
                    @click="tab='map'">Map View</button>
        </div>

        <!-- Filter -->
        <div>
            <input type="text" placeholder="Filter by region/vehicle..." x-model="filterText"
                   class="border rounded px-3 py-1 w-full md:w-1/2"/>
        </div>

        <!-- Table -->
        <div x-show="tab==='table'" class="space-y-6">
            @php $grouped = $vehicles->groupBy('region'); @endphp
            @foreach($grouped as $region => $group)
                <div class="border rounded shadow-sm">
                    <button class="w-full flex justify-between px-4 py-2 bg-gray-100 font-medium text-left"
                            @click="$el.nextElementSibling.classList.toggle('hidden')">
                        <span>{{ $region }} ({{ $group->count() }} vehicles)</span>
                        <span class="text-gray-500">▼</span>
                    </button>
                    <div class="hidden">
                        <table class="w-full border-t">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left">Name</th>
                                <th class="px-3 py-2 text-left">Last Seen</th>
                                <th class="px-3 py-2 text-left">Speed</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($group as $vehicle)
                                <tr x-show="!filterText
                                    || '{{ strtolower($region) }}'.includes(filterText.toLowerCase())
                                    || '{{ strtolower($vehicle['name']) }}'.includes(filterText.toLowerCase())"
                                    class="border-t">
                                    <td class="px-3 py-2">{{ $vehicle['name'] }}</td>
                                    <td class="px-3 py-2">{{ $vehicle['last_seen'] }}</td>
                                    <td class="px-3 py-2">{{ $vehicle['speed'] }} km/h</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Map -->
        <div x-show="tab==='map'" class="p-4 h-[600px]">
            <div id="fleet-map" class="w-full h-full rounded-xl border"></div>
        </div>

    </div>

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const map = L.map('fleet-map').setView([0,0], 5);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
                maxZoom:18,
                attribution:'&copy; OpenStreetMap contributors'
            }).addTo(map);

            const vehicles = @json($vehicles);
            const markers = [];

            vehicles.forEach(v => {
                if(v.latitude && v.longitude){
                    const marker = L.marker([v.latitude,v.longitude])
                        .bindPopup(`<b>${v.name}</b><br/>Region: ${v.region}<br/>Last seen: ${v.last_seen}<br/>Speed: ${v.speed} km/h`);
                    marker.addTo(map);
                    markers.push(marker);
                }
            });

            if(markers.length){
                const group = L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.2));
            }
        });
    </script>
</x-filament-panels::page>
