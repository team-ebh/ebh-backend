@php
    $activities = $getState();
@endphp

@if($activities->isEmpty())
    <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-6 text-center">
        <div class="flex flex-col items-center justify-center space-y-2">
            <x-filament::icon
                icon="heroicon-o-clock"
                class="h-12 w-12 text-gray-400 dark:text-gray-600"
            />
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ trans('general.admin.no_activities') }}
            </p>
        </div>
    </div>
@else
    <div class="space-y-6 py-2">
        @foreach($activities as $activity)
            @php
                $eventColor = match($activity['event']) {
                    'created' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
                    'updated' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
                    'deleted' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
                    'restored' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                };

                $eventIcon = match($activity['event']) {
                    'created' => 'heroicon-o-plus-circle',
                    'updated' => 'heroicon-o-pencil-square',
                    'deleted' => 'heroicon-o-trash',
                    'restored' => 'heroicon-o-arrow-path',
                    default => 'heroicon-o-document-text',
                };

                $dotColor = match($activity['event']) {
                    'created' => 'bg-green-500 ring-green-200 dark:ring-green-800',
                    'updated' => 'bg-blue-500 ring-blue-200 dark:ring-blue-800',
                    'deleted' => 'bg-red-500 ring-red-200 dark:ring-red-800',
                    'restored' => 'bg-yellow-500 ring-yellow-200 dark:ring-yellow-800',
                    default => 'bg-gray-400 ring-gray-200 dark:ring-gray-700',
                };
            @endphp

            <div class="relative flex gap-x-4">
                @if(!$loop->last)
                    <div class="absolute left-0 top-0 flex w-8 justify-center -bottom-6">
                        <div class="w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                    </div>
                @endif

                <div class="relative flex h-8 w-8 flex-none items-center justify-center">
                    <div class="h-3 w-3 rounded-full {{ $dotColor }} ring-4 ring-white dark:ring-gray-900"></div>
                </div>

                <div class="flex-auto rounded-lg bg-white dark:bg-gray-800 p-4 ring-1 ring-gray-200 dark:ring-gray-700 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 flex-wrap">
                                <x-filament::icon
                                    :icon="$eventIcon"
                                    class="h-5 w-5 text-gray-500 dark:text-gray-400"
                                />
                                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold {{ $eventColor }}">
                                    {{ ucfirst($activity['event']) }}
                                </span>
                                <p class="text-sm font-medium leading-6 text-gray-900 dark:text-gray-100">
                                    {{ $activity['description'] }}
                                </p>
                            </div>

                            @if($activity['causer_name'])
                                <div class="mt-2 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                    <x-filament::icon
                                        icon="heroicon-o-user"
                                        class="h-4 w-4"
                                    />
                                    <span>{{ trans('general.admin.performed_by') }}: <strong class="font-medium">{{ $activity['causer_name'] }}</strong></span>
                                </div>
                            @endif
                        </div>

                        <time datetime="{{ $activity['created_at'] }}" class="flex-none text-xs font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            {{ $activity['diff_for_humans'] }}
                        </time>
                    </div>

                    @if(!empty($activity['changes']))
                        <div class="mt-3 border-t border-gray-100 dark:border-gray-700 pt-3">
                            <details class="group cursor-pointer">
                                <summary class="flex items-center gap-2 text-xs font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 select-none">
                                    <x-filament::icon
                                        icon="heroicon-o-chevron-right"
                                        class="h-4 w-4 transition-transform group-open:rotate-90"
                                    />
                                    {{ trans('general.admin.view_changes') }} ({{ count($activity['changes']) }})
                                </summary>
                                <div class="mt-3 space-y-2 pl-6">
                                    @foreach($activity['changes'] as $change)
                                        <div class="flex flex-col gap-1 text-xs bg-gray-50 dark:bg-gray-900/50 p-2 rounded">
                                            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $change['field'] }}</span>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                @if($change['old'] !== null)
                                                    <span class="text-red-600 dark:text-red-400 line-through break-all">
                                                        {{ is_array($change['old']) ? json_encode($change['old']) : Str::limit($change['old'], 50) }}
                                                    </span>
                                                    <x-filament::icon
                                                        icon="heroicon-o-arrow-right"
                                                        class="h-3 w-3 text-gray-400 flex-shrink-0"
                                                    />
                                                @endif
                                                <span class="text-green-600 dark:text-green-400 font-medium break-all">
                                                    {{ is_array($change['new']) ? json_encode($change['new']) : Str::limit($change['new'], 50) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif