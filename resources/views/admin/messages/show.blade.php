@extends('admin.layout')
@section('title', 'Message — '.$message->subject)

@section('content')
<div class="mx-auto flex max-w-2xl flex-col gap-4">

    <a href="{{ route('admin.messages.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Messages</a>

    <div class="rounded-2xl bg-white p-6 shadow-card">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="font-display text-lg font-semibold text-nuit-900">{{ $message->subject }}</p>
                <p class="mt-1 text-sm text-nuit-500">
                    {{ $message->name }} · <a href="mailto:{{ $message->email }}" class="text-terracotta-600 hover:underline">{{ $message->email }}</a>
                    @if ($message->phone) · {{ $message->phone }} @endif
                </p>
                <p class="mt-1 text-xs text-nuit-400">Reçu le {{ $message->created_at->translatedFormat('D d M Y à H:i') }}</p>
            </div>
            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold
                {{ ['new' => 'bg-laiton-100 text-laiton-800', 'read' => 'bg-nuit-100 text-nuit-700', 'handled' => 'bg-emerald-100 text-emerald-800'][$message->status] }}">
                {{ $message->statusLabel() }}
            </span>
        </div>

        <div class="mt-5 whitespace-pre-line rounded-xl bg-sable-50 p-4 text-sm leading-relaxed text-nuit-800">{{ $message->message }}</div>

        @if ($message->handler && $message->handled_at)
            <p class="mt-3 text-xs text-nuit-400">Traité par {{ $message->handler->name }} le {{ $message->handled_at->translatedFormat('d/m/Y à H:i') }}.</p>
        @endif

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('RE: '.$message->subject) }}"
               class="rounded-xl bg-nuit-900 px-5 py-2 text-sm font-semibold text-white hover:bg-nuit-800">
                Répondre par e-mail
            </a>
            <form method="POST" action="{{ route('admin.messages.handle', $message) }}">
                @csrf
                <button class="rounded-xl border border-nuit-200 px-4 py-2 text-sm font-medium text-nuit-700 hover:border-terracotta-400 hover:text-terracotta-600">
                    {{ $message->status === 'handled' ? 'Rouvrir' : 'Marquer comme traité' }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
