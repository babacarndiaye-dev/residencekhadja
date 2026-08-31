<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ClockController extends Controller
{
    /** Borne de pointage (§44) — le scan du badge QR est la méthode principale. */
    public function show(?string $matricule = null)
    {
        $employee = $matricule
            ? Employee::active()->tracksAttendance()->where('matricule', $matricule)->first()
            : null;

        return view('pages.clock', [
            'matricule' => $employee?->matricule,
            'employeeName' => $employee?->fullName(),
        ]);
    }

    /** Pointage par scan de badge (URL signée) : le badge fait office d'identifiant. */
    public function scan(Request $request, Employee $employee)
    {
        try {
            $result = AttendanceService::clock($employee, 'borne');
        } catch (ValidationException $e) {
            $payload = ['ok' => false, 'employee' => $employee, 'message' => $e->validator->errors()->first()];

            return $request->wantsJson()
                ? response()->json(['ok' => false, 'message' => $payload['message']])
                : response()->view('pages.clock-result', $payload)->header('Cache-Control', 'no-store');
        }

        $log = $result['log'];
        $action = $result['action'];
        $when = $action === 'in' ? $log->clock_in : $log->clock_out;

        $message = $action === 'in'
            ? 'Entrée pointée à '.$when->format('H:i').($log->late_minutes ? ' · retard '.$log->late_minutes.' min' : '')
            : 'Sortie pointée à '.$when->format('H:i').' · '.$log->workedHours().' h travaillées'
                .($log->overtime_minutes ? ' (+'.$log->overtimeHours().' h supp.)' : '');

        $payload = [
            'ok' => true,
            'action' => $action,
            'employee' => $employee,
            'name' => $employee->fullName(),
            'first_name' => $employee->first_name,
            'photo' => '/pointage/photo/'.$employee->matricule,
            'speech' => $action === 'in' ? 'Bienvenue à l’hôtel !' : 'À bientôt !',
            'message' => $message,
        ];

        return $request->wantsJson()
            ? response()->json($payload)
            : response()->view('pages.clock-result', $payload)->header('Cache-Control', 'no-store');
    }

    /** Pointage de secours : matricule + code personnel. */
    public function clock(Request $request)
    {
        $data = $request->validate([
            'matricule' => ['required', 'string', 'max:30'],
            'pin' => ['required', 'string', 'max:12'],
        ]);

        $result = AttendanceService::clockByPin(trim($data['matricule']), $data['pin']);

        $log = $result['log'];
        $when = $result['action'] === 'in' ? $log->clock_in : $log->clock_out;
        $msg = $result['action'] === 'in'
            ? "Bienvenue {$result['employee']->first_name} — entrée pointée à {$when->format('H:i')}."
                .($log->late_minutes ? " Retard de {$log->late_minutes} min." : '')
            : "À bientôt {$result['employee']->first_name} — sortie pointée à {$when->format('H:i')}. "
                ."Temps travaillé : {$log->workedHours()} h"
                .($log->overtime_minutes ? " (dont {$log->overtimeHours()} h supp.)" : '').'.';

        return back()->with('status', $msg);
    }

    /** Photo de l'agent (ou avatar généré), servie publiquement pour la borne. */
    public function photo(Employee $employee)
    {
        if ($employee->photo_path && Storage::disk('local')->exists($employee->photo_path)) {
            return response(Storage::disk('local')->get($employee->photo_path), 200, [
                'Content-Type' => Storage::disk('local')->mimeType($employee->photo_path) ?: 'image/jpeg',
                'Cache-Control' => 'private, max-age=86400',
            ]);
        }

        $initials = mb_strtoupper(mb_substr($employee->first_name, 0, 1).mb_substr($employee->last_name, 0, 1));
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 160">'
            .'<rect width="160" height="160" fill="#374249"/>'
            .'<text x="80" y="84" text-anchor="middle" font-family="Georgia, serif" font-size="66" fill="#f4f5f3">'.$initials.'</text></svg>';

        return response($svg, 200, ['Content-Type' => 'image/svg+xml']);
    }
}
