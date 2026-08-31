<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Hotel;
use App\Models\ReportRun;
use App\Models\ReportSchedule;
use App\Services\Analytics;
use App\Services\KhadijaAI;
use App\Services\ReportRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BiController extends Controller
{
    public function dashboard(Request $request)
    {
        [$from, $to] = Analytics::resolveRange(
            $request->query('period', '30d'),
            $request->query('from'),
            $request->query('to'),
        );

        $overview = Analytics::overview($from, $to);

        $spark = [];
        foreach (['total_revenue', 'occupancy', 'adr', 'covers'] as $key) {
            $spark[$key] = Analytics::sparkline(Analytics::series($key, $to->copy()->subDays(29), $to));
        }

        return view('admin.bi.dashboard', [
            'from' => $from,
            'to' => $to,
            'period' => $request->query('period', '30d'),
            'o' => $overview,
            'spark' => $spark,
            'alerts' => collect($overview['kpi'])->reject->good->values(),
        ]);
    }

    public function reports(Request $request)
    {
        [$from, $to] = Analytics::resolveRange($request->query('period', '30d'), $request->query('from'), $request->query('to'));

        return view('admin.bi.reports', [
            'definitions' => collect(ReportRegistry::definitions())->groupBy('group'),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function report(Request $request, string $key)
    {
        abort_unless(ReportRegistry::exists($key), 404);
        [$from, $to] = Analytics::resolveRange($request->query('period', '30d'), $request->query('from'), $request->query('to'));

        return view('admin.bi.report', [
            'report' => ReportRegistry::run($key, $from, $to),
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function export(Request $request, string $key): StreamedResponse
    {
        abort_unless(ReportRegistry::exists($key), 404);
        [$from, $to] = Analytics::resolveRange($request->query('period', '30d'), $request->query('from'), $request->query('to'));

        $report = ReportRegistry::run($key, $from, $to);
        $csv = ReportRegistry::toCsv($report);
        $filename = "{$key}_{$from->toDateString()}_{$to->toDateString()}.csv";

        return response()->streamDownload(fn () => print ("\xEF\xBB\xBF".$csv), $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /* --------------------------- Planifications ----------------------- */

    public function schedules()
    {
        return view('admin.bi.schedules', [
            'schedules' => ReportSchedule::with('createdBy')->latest()->get(),
            'runs' => ReportRun::with('schedule')->latest('created_at')->limit(20)->get(),
            'reports' => ReportRegistry::definitions(),
            'frequencies' => ReportSchedule::FREQUENCIES,
        ]);
    }

    public function storeSchedule(Request $request)
    {
        $data = $request->validate([
            'report_key' => ['required', Rule::in(array_keys(ReportRegistry::definitions()))],
            'frequency' => ['required', Rule::in(array_keys(ReportSchedule::FREQUENCIES))],
            'range_days' => ['required', 'integer', 'min:1', 'max:366'],
            'recipients' => ['nullable', 'string', 'max:500'],
        ]);

        ReportSchedule::create([
            'hotel_id' => Hotel::current()->id,
            'report_key' => $data['report_key'],
            'frequency' => $data['frequency'],
            'range_days' => $data['range_days'],
            'recipients' => array_values(array_filter(array_map('trim', explode(',', $data['recipients'] ?? '')))),
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Rapport planifié.');
    }

    public function toggleSchedule(ReportSchedule $schedule)
    {
        $schedule->update(['is_active' => ! $schedule->is_active]);

        return back()->with('status', $schedule->is_active ? 'Planification activée.' : 'Planification suspendue.');
    }

    public function destroySchedule(ReportSchedule $schedule)
    {
        $schedule->delete();

        return back()->with('status', 'Planification supprimée.');
    }

    /* ------------------------------ KHADJA AI ------------------------------ */

    public function assistant()
    {
        return view('admin.bi.assistant', [
            'configured' => KhadijaAI::configured(),
            'samples' => [
                "Quel est mon chiffre d'affaires restaurant aujourd'hui ?",
                'Quel plat est le plus rentable ce mois-ci ?',
                'Quels produits sont bientôt en rupture ?',
                'Comment se répartissent les encaissements par moyen de paiement ?',
            ],
        ]);
    }

    public function ask(Request $request)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        $result = KhadijaAI::ask($data['question'], $request->user());
        AuditLog::record('ai.query', null, ['q' => Str::limit($data['question'], 120), 'ok' => $result['ok']]);

        return view('admin.bi.assistant', [
            'configured' => KhadijaAI::configured(),
            'question' => $data['question'],
            'answer' => $result['answer'],
            'ok' => $result['ok'],
            'samples' => [
                "Quel est mon chiffre d'affaires restaurant aujourd'hui ?",
                'Quel plat est le plus rentable ce mois-ci ?',
                'Quels produits sont bientôt en rupture ?',
                'Comment se répartissent les encaissements par moyen de paiement ?',
            ],
        ]);
    }
}
