<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show()
    {
        return view('pages.contact');
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:3000'],
            'consent' => ['accepted'],
        ], [], [
            'name' => 'nom',
            'subject' => 'objet',
            'message' => 'message',
            'consent' => 'consentement',
        ]);

        $message = ContactMessage::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255) ?: null,
        ]);

        Mail::to(config('hotel.contact.email'))->queue(new ContactMessageReceived($message));

        Notify::roles(
            ['reception', 'direction'],
            'Nouveau message de contact',
            $message->name.' — '.$message->subject,
            route('admin.messages.show', $message, false),
            icon: '✉️',
        );

        return back()->with('status', "Merci {$data['name']}, votre message a bien été transmis. "
            .'Notre équipe vous répond sous 24 heures.');
    }
}
