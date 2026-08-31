<?php

namespace Tests\Feature;

use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Accounting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function finance(): User
    {
        return User::where('role', 'finance')->firstOrFail();
    }

    private function direction(): User
    {
        return User::where('role', 'direction')->firstOrFail();
    }

    /** Écriture de vente TTC avec TVA collectée. */
    private function sale(int $ht, int $vat, $date = null): JournalEntry
    {
        return Accounting::post('VT', $date ?? Carbon::today()->toDateString(), 'Vente test', [
            ['account' => '571000', 'debit' => $ht + $vat],
            ['account' => '706200', 'credit' => $ht],
            ['account' => '443100', 'credit' => $vat],
        ]);
    }

    private function purchase(int $ht, int $vat, $date = null): JournalEntry
    {
        return Accounting::post('AC', $date ?? Carbon::today()->toDateString(), 'Achat test', [
            ['account' => '601000', 'debit' => $ht],
            ['account' => '445100', 'debit' => $vat],
            ['account' => '401000', 'credit' => $ht + $vat],
        ]);
    }

    public function test_income_statement_reflects_new_products_and_charges(): void
    {
        $fy = Accounting::currentFiscalYear();
        $before = Accounting::incomeStatement($fy->starts_on->toDateString(), $fy->ends_on->toDateString());

        $this->sale(100_000, 18_000);
        $this->purchase(40_000, 7_200);

        $after = Accounting::incomeStatement($fy->starts_on->toDateString(), $fy->ends_on->toDateString());

        $this->assertSame($before['total_income'] + 100_000, $after['total_income']);
        $this->assertSame($before['total_expense'] + 40_000, $after['total_expense']);
        $this->assertSame($before['result'] + 60_000, $after['result']);
    }

    public function test_balance_sheet_stays_balanced(): void
    {
        $this->sale(250_000, 45_000);
        $this->purchase(90_000, 16_200);

        $sheet = Accounting::balanceSheet(Carbon::today()->toDateString());

        $this->assertTrue($sheet['balanced'], 'Actif '.$sheet['total_assets'].' ≠ Passif '.$sheet['total_liabilities']);
    }

    public function test_vat_return_nets_collected_minus_deductible(): void
    {
        $this->sale(500_000, 90_000);   // TVA collectée 90 000
        $this->purchase(100_000, 18_000); // TVA déductible 18 000

        $fy = Accounting::currentFiscalYear();
        $vat = Accounting::vatReturn($fy->starts_on->toDateString(), $fy->ends_on->toDateString());

        $this->assertSame(90_000, $vat['collected']);
        $this->assertSame(18_000, $vat['deductible']);
        $this->assertSame(72_000, $vat['net']);
        $this->assertSame(72_000, $vat['due']);
    }

    public function test_closing_a_fiscal_year_regroups_pnl_and_locks_the_period(): void
    {
        $this->sale(1_000_000, 180_000);
        $fy = Accounting::currentFiscalYear();
        $operatingResult = Accounting::incomeStatement($fy->starts_on->toDateString(), $fy->ends_on->toDateString())['result'];
        $this->assertGreaterThan(0, $operatingResult);

        $fy = Accounting::closeFiscalYear($fy, $this->direction()->id);

        $this->assertSame('closed', $fy->status);
        $this->assertSame($operatingResult, $fy->result_amount);
        $this->assertDatabaseHas('journal_entries', ['journal' => 'CL', 'id' => $fy->closing_entry_id]);

        // Le compte de résultat (hors clôture) reste inchangé.
        $this->assertSame($operatingResult, Accounting::incomeStatement($fy->starts_on->toDateString(), $fy->ends_on->toDateString())['result']);

        // Les comptes de gestion sont soldés dans la balance (clôture incluse).
        $balance = Accounting::trialBalance('1900-01-01', $fy->ends_on->toDateString());
        $this->assertSame(0, $balance->firstWhere('code', '706200')['balance']);
        $this->assertSame(-$operatingResult, $balance->firstWhere('code', '120000')['balance']); // solde créditeur

        // Période verrouillée.
        $this->assertTrue(Accounting::isLocked($fy->starts_on));
        $this->expectException(RuntimeException::class);
        Accounting::post('OD', $fy->starts_on->toDateString(), 'Après clôture', [
            ['account' => '571000', 'debit' => 1000],
            ['account' => '758000', 'credit' => 1000],
        ]);
    }

    public function test_balance_sheet_still_balances_after_closing(): void
    {
        $this->sale(300_000, 54_000);
        $fy = Accounting::closeFiscalYear(Accounting::currentFiscalYear(), $this->direction()->id);

        $this->assertTrue(Accounting::balanceSheet($fy->ends_on->toDateString())['balanced']);
    }

    public function test_reopening_a_year_removes_the_closing_entry(): void
    {
        $this->sale(120_000, 21_600);
        $fy = Accounting::closeFiscalYear(Accounting::currentFiscalYear(), $this->direction()->id);
        $closingId = $fy->closing_entry_id;

        $fy = Accounting::reopenFiscalYear($fy);

        $this->assertSame('open', $fy->status);
        $this->assertNull($fy->closing_entry_id);
        $this->assertDatabaseMissing('journal_entries', ['id' => $closingId]);
        $this->assertFalse(Accounting::isLocked($fy->starts_on));
    }

    public function test_reversing_an_entry_creates_a_balanced_mirror(): void
    {
        $entry = Accounting::post('OD', Carbon::today()->toDateString(), 'À contre-passer', [
            ['account' => '571000', 'debit' => 5000],
            ['account' => '758000', 'credit' => 5000],
        ]);

        $reversal = Accounting::reverse($entry->load('lines.account'), $this->finance()->id);

        $this->assertSame('OD', $reversal->journal);
        $this->assertStringContainsString($entry->reference, $reversal->label);

        $mirror = $reversal->lines->first(fn ($l) => $l->account->code === '571000');
        $this->assertSame(0, $mirror->debit);
        $this->assertSame(5000, $mirror->credit);
    }

    public function test_manual_entry_form_refuses_a_date_in_a_closed_year(): void
    {
        $past = FiscalYear::create([
            'hotel_id' => 1, 'label' => '2024',
            'starts_on' => '2024-01-01', 'ends_on' => '2024-12-31', 'status' => 'open',
        ]);
        Accounting::closeFiscalYear($past, $this->direction()->id);

        $before = JournalEntry::count();

        $this->actingAs($this->finance())
            ->post(route('admin.accounting.entries.store'), [
                'journal' => 'OD', 'entry_date' => '2024-06-15', 'label' => 'Trop tard',
                'lines' => [
                    ['code' => '571000', 'debit' => 1000, 'credit' => 0],
                    ['code' => '758000', 'debit' => 0, 'credit' => 1000],
                ],
            ])
            ->assertSessionHasErrors('lines');

        $this->assertSame($before, JournalEntry::count());
    }

    public function test_reports_are_finance_or_direction_and_closing_is_direction_only(): void
    {
        $reception = User::where('role', 'reception')->firstOrFail();
        $fy = Accounting::currentFiscalYear();

        $this->actingAs($this->finance())->get(route('admin.accounting.income_statement'))->assertOk();
        $this->actingAs($this->finance())->get(route('admin.accounting.balance_sheet'))->assertOk();
        $this->actingAs($this->finance())->get(route('admin.accounting.vat'))->assertOk();
        $this->actingAs($this->finance())->get(route('admin.accounting.general_ledger'))->assertOk();
        $this->actingAs($this->finance())->get(route('admin.accounting.fiscal_years'))->assertOk();

        $this->actingAs($reception)->get(route('admin.accounting.income_statement'))->assertForbidden();

        // Clôture : direction uniquement.
        $this->actingAs($this->finance())->post(route('admin.accounting.fiscal_years.close', $fy))->assertForbidden();
        $this->actingAs($this->direction())->post(route('admin.accounting.fiscal_years.close', $fy))->assertRedirect();
        $this->assertSame('closed', $fy->fresh()->status);
    }
}
