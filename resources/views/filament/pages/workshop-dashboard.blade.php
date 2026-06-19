<x-filament::page>
    <div class="space-y-6">
        {{-- Stats Row --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
            <x-filament::card>
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary-600">{{ $this->totalInWorkshop }}</div>
                    <div class="text-sm text-gray-500">In Workshop</div>
                </div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-center">
                    <div class="text-3xl font-bold text-warning-600">{{ $this->byStatus['pending_approval'] }}</div>
                    <div class="text-sm text-gray-500">Pending Approval</div>
                </div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-center">
                    <div class="text-3xl font-bold text-info-600">{{ $this->byStatus['in_progress'] }}</div>
                    <div class="text-sm text-gray-500">In Progress</div>
                </div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-center">
                    <div class="text-3xl font-bold text-success-600">{{ $this->byStatus['completed'] }}</div>
                    <div class="text-sm text-gray-500">Completed</div>
                </div>
            </x-filament::card>
            <x-filament::card>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-600">{{ $this->releasedPool->count() }}</div>
                    <div class="text-sm text-gray-500">Released Pool</div>
                </div>
            </x-filament::card>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Recent Requests --}}
            <x-filament::card>
                <x-slot name="header">
                    <h3 class="text-lg font-medium">Recent Repair Requests</h3>
                </x-slot>
                <div class="divide-y">
                    @forelse($this->recentRequests as $request)
                        <div class="flex items-center justify-between py-2">
                            <div>
                                <span class="font-medium">{{ $request->reference }}</span>
                                <span class="ml-2 text-sm text-gray-500">{{ $request->vehicle->plate_number }}</span>
                            </div>
                            <div>
                                <span @class([
                                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-gray-100 text-gray-800' => $request->status === 'draft',
                                    'bg-yellow-100 text-yellow-800' => $request->status === 'pending_approval',
                                    'bg-blue-100 text-blue-800' => $request->status === 'approved',
                                    'bg-purple-100 text-purple-800' => $request->status === 'parts_pending',
                                    'bg-indigo-100 text-indigo-800' => $request->status === 'in_progress',
                                    'bg-green-100 text-green-800' => $request->status === 'completed',
                                    'bg-gray-100 text-gray-800' => $request->status === 'released',
                                    'bg-red-100 text-red-800' => $request->status === 'cancelled',
                                ])>{{ str_replace('_', ' ', $request->status) }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-gray-500">No repair requests yet.</p>
                    @endforelse
                </div>
            </x-filament::card>

            {{-- Released Pool --}}
            <x-filament::card>
                <x-slot name="header">
                    <h3 class="text-lg font-medium">Released Pool</h3>
                </x-slot>
                <div class="divide-y">
                    @forelse($this->releasedPool as $vehicle)
                        <div class="flex items-center justify-between py-2">
                            <div>
                                <span class="font-medium">{{ $vehicle->plate_number }}</span>
                                <span class="ml-2 text-sm text-gray-500">{{ $vehicle->make }} {{ $vehicle->model }}</span>
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ $vehicle->latestRepairRequest?->release?->unresolved_issues ? '⚠ Has notes' : '✓ No issues' }}
                            </div>
                        </div>
                    @empty
                        <p class="py-4 text-sm text-gray-500">No vehicles in released pool.</p>
                    @endforelse
                </div>
            </x-filament::card>
        </div>

        {{-- Low Stock Alerts --}}
        @if($this->lowStockParts->isNotEmpty())
            <x-filament::card>
                <x-slot name="header">
                    <h3 class="text-lg font-medium text-danger-600">⚠ Low Stock Alerts</h3>
                </x-slot>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-2">Part</th>
                            <th class="py-2">Warehouse</th>
                            <th class="py-2">Current</th>
                            <th class="py-2">Min</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($this->lowStockParts as $stock)
                            <tr>
                                <td class="py-2 font-medium">{{ $stock->part->name }}</td>
                                <td class="py-2 text-gray-500">{{ $stock->warehouse->name }}</td>
                                <td class="py-2 text-danger-600">{{ $stock->quantity }}</td>
                                <td class="py-2">{{ $stock->min_quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-filament::card>
        @endif
    </div>
</x-filament::page>
