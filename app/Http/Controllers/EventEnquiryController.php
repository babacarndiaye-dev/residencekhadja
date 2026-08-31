<?php

namespace App\Http\Controllers;

use App\Services\EventQuoteRequest;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Formulaire public « Séminaires & Événements » (/seminaires-evenements) :
 * une demande de devis structurée qui crée une affaire + un devis brouillon
 * pré-rempli dans le pipeline commercial.
 */
class EventEnquiryController extends Controller
{
    public function store(Request $request)
    {
        // Pot de miel : un robot remplit le champ caché « website » → on répond OK sans rien créer.
        if (filled($request->input('website'))) {
            return back()->with('status', 'Merci, votre demande de devis a bien été envoyée.');
        }

        $cateringCodes = array_column(config('events.catering_packages'), 'code');
        $equipmentCodes = array_column(config('events.av_equipment'), 'code');

        $data = $request->validate([
            'contact_name' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:150'],
            'contact_email' => ['required', 'email', 'max:180'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'event_type' => ['required', Rule::in(array_keys(config('events.event_types')))],
            'event_space_id' => ['nullable', Rule::exists('event_spaces', 'id')],
            'date' => ['nullable', 'date', 'after_or_equal:today'],
            'duration' => ['required', Rule::in(['full_day', 'half_day'])],
            'pax' => ['required', 'integer', 'min:1', 'max:5000'],
            'layout' => ['nullable', Rule::in(config('events.layouts'))],
            'catering' => ['nullable', 'array'],
            'catering.*' => [Rule::in($cateringCodes)],
            'equipment' => ['nullable', 'array'],
            'equipment.*' => [Rule::in($equipmentCodes)],
            'message' => ['nullable', 'string', 'max:2000'],
            'consent' => ['accepted'],
        ], [], [
            'contact_name' => 'nom',
            'contact_email' => 'e-mail',
            'event_type' => 'type d’événement',
            'duration' => 'durée',
            'pax' => 'nombre de participants',
            'consent' => 'consentement',
        ]);

        $quote = EventQuoteRequest::create($data, null, 'Demande reçue via le formulaire du site web.');
        $lead = $quote->lead;

        Notify::roles(
            ['commercial', 'direction'],
            'Demande de devis (site web)',
            $lead->contact_name.' · '.config("events.event_types.{$lead->event_type}").' · '.$lead->pax.' pers.',
            route('admin.events.quotes.edit', $quote, false),
            icon: '📩',
        );

        return back()->with('status',
            "Merci {$data['contact_name']}, votre demande de devis est enregistrée (réf. {$lead->reference}). "
            .'Notre équipe commerciale revient vers vous sous 24 heures ouvrées avec une proposition chiffrée.');
    }
}
