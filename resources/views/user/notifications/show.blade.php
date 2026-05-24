@extends('layouts.app')

@section('title', data_get($data, 'title', 'Notification'))

@section('content')
<div class="max-w-4xl mx-auto px-4 py-10">
    <div class="flex items-center justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ data_get($data, 'title', 'Notification') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ data_get($data, 'timestamp', $notification->created_at->toDateTimeString()) }}</p>
        </div>
        <a href="{{ route('user.notifications') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
            <i class="bi bi-arrow-left"></i>
            Back to notifications
        </a>
    </div>

    @if(!empty($warning))
        <div class="mb-6 rounded-2xl border border-yellow-200 bg-yellow-50 dark:border-yellow-700 dark:bg-yellow-950/10 p-4 text-sm text-yellow-800 dark:text-yellow-200">
            <i class="bi bi-exclamation-triangle-fill mr-2"></i>
            {{ $warning }}
        </div>
    @endif

    @if(isset($isDeleted) && $isDeleted)
        <div class="rounded-3xl border border-red-200 dark:border-red-800 bg-gradient-to-br from-red-50 to-red-100 dark:from-red-950/20 dark:to-red-950/10 p-8 text-center">
            <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                <i class="bi bi-trash3 text-3xl text-red-600 dark:text-red-400"></i>
            </div>
            <h2 class="text-xl font-semibold text-red-800 dark:text-red-200 mb-3">Content No Longer Available</h2>
            <p class="text-red-700 dark:text-red-300 mb-4 max-w-md mx-auto">
                The {{ data_get($data, 'deleted_type', 'item') }} associated with this notification has been deleted.
            </p>
            <div class="mt-4 pt-4 border-t border-red-200 dark:border-red-800/50 max-w-md mx-auto">
                <div class="grid gap-3 text-sm">
                    @if(data_get($data, 'ticket_number'))
                        <div class="flex justify-between items-center">
                            <span class="text-red-600 dark:text-red-400 font-medium">Ticket Number:</span>
                            <span class="text-red-800 dark:text-red-200">{{ data_get($data, 'ticket_number') }}</span>
                        </div>
                    @endif
                    @if(data_get($data, 'ticket_subject'))
                        <div class="flex justify-between items-center">
                            <span class="text-red-600 dark:text-red-400 font-medium">Ticket Subject:</span>
                            <span class="text-red-800 dark:text-red-200">{{ data_get($data, 'ticket_subject') }}</span>
                        </div>
                    @endif
                    @if(data_get($data, 'deleted_by.name'))
                        <div class="flex justify-between items-center">
                            <span class="text-red-600 dark:text-red-400 font-medium">Deleted By:</span>
                            <span class="text-red-800 dark:text-red-200">{{ data_get($data, 'deleted_by.name') }}</span>
                        </div>
                    @endif
                    @if(data_get($data, 'deleted_at'))
                        <div class="flex justify-between items-center">
                            <span class="text-red-600 dark:text-red-400 font-medium">Deleted At:</span>
                            <span class="text-red-800 dark:text-red-200">{{ \Carbon\Carbon::parse(data_get($data, 'deleted_at'))->format('F j, Y g:i A') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="rounded-3xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-950 shadow-sm overflow-hidden">
            <div class="p-6">
                <div class="mb-6">
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">{{ data_get($data, 'message', 'No notification details available.') }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @if(data_get($data, 'ticket_number'))
                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-900 p-4">
                            <p class="text-xs uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">Ticket Number</p>
                            <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{{ data_get($data, 'ticket_number') }}</p>
                        </div>
                    @endif

                    @if(data_get($data, 'ticket_subject'))
                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-900 p-4">
                            <p class="text-xs uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">Ticket Subject</p>
                            <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{{ data_get($data, 'ticket_subject') }}</p>
                        </div>
                    @endif

                    @if(data_get($data, 'ticket_status'))
                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-900 p-4">
                            <p class="text-xs uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">Status</p>
                            <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{{ ucfirst(data_get($data, 'ticket_status')) }}</p>
                        </div>
                    @endif

                    @if(data_get($data, 'ticket_priority'))
                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-900 p-4">
                            <p class="text-xs uppercase tracking-[0.24em] text-gray-500 dark:text-gray-400">Priority</p>
                            <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{{ ucfirst(data_get($data, 'ticket_priority')) }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
@endsection