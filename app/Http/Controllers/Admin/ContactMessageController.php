<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(ContactMessage::STATUSES))],
        ]);

        $messages = ContactMessage::query()
            ->with('handler')
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.messages.index', [
            'messages' => $messages,
            'status' => $filters['status'] ?? null,
            'newCount' => ContactMessage::where('status', 'new')->count(),
        ]);
    }

    public function show(ContactMessage $message)
    {
        if ($message->status === 'new') {
            $message->update(['status' => 'read']);
        }

        return view('admin.messages.show', ['message' => $message]);
    }

    public function handle(Request $request, ContactMessage $message)
    {
        $reopen = $message->status === 'handled';

        $message->update([
            'status' => $reopen ? 'read' : 'handled',
            'handled_by' => $reopen ? null : $request->user()->id,
            'handled_at' => $reopen ? null : now(),
        ]);

        AuditLog::record($reopen ? 'contact_message.reopened' : 'contact_message.handled', $message);

        return back()->with('status', $reopen ? 'Message rouvert.' : 'Message marqué comme traité.');
    }
}
