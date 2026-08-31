@extends('admin.layout')
@section('title', 'Notifications')

@section('content')
<div class="mx-auto flex max-w-2xl flex-col gap-4">

    <div class="flex items-center justify-between">
        <p class="text-sm text-nuit-500">{{ $unreadCount }} non lue(s) sur {{ $notifications->total() }}.</p>
        @if ($unreadCount)
            <form method="POST" action="{{ route('admin.notifications.read_all') }}">
                @csrf
                <button class="rounded-lg border border-nuit-200 px-3 py-1.5 text-sm font-medium text-nuit-700 hover:border-terracotta-400 hover:text-terracotta-600">
                    Tout marquer comme lu
                </button>
            </form>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl bg-white shadow-card">
        @forelse ($notifications as $n)
            <form method="POST" action="{{ route('admin.notifications.read', $n->id) }}">
                @csrf
                <button @class([
                    'flex w-full items-start gap-3 border-b border-sable-100 px-4 py-3.5 text-left hover:bg-sable-50',
                    'bg-sable-50/60' => ! $n->read_at,
                ])>
                    <span class="mt-0.5 text-lg leading-none">{{ $n->data['icon'] ?? '🔔' }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-nuit-900">{{ $n->data['title'] ?? 'Notification' }}</span>
                            @unless ($n->read_at)<span class="h-1.5 w-1.5 shrink-0 rounded-full bg-terracotta-500"></span>@endunless
                        </span>
                        @if (! empty($n->data['body']))<span class="mt-0.5 block text-sm text-nuit-600">{{ $n->data['body'] }}</span>@endif
                        <span class="mt-1 block text-xs text-nuit-400">{{ $n->created_at->translatedFormat('D d M à H:i') }} · {{ $n->created_at->diffForHumans() }}</span>
                    </span>
                    @if (! empty($n->data['url']))<span class="mt-1 text-xs font-medium text-terracotta-600">Ouvrir →</span>@endif
                </button>
            </form>
        @empty
            <p class="px-4 py-12 text-center text-sm text-nuit-400">Aucune notification.</p>
        @endforelse
    </div>

    <div>{{ $notifications->links() }}</div>
</div>
@endsection
