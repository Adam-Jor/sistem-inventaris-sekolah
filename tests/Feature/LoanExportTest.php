<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_loans_to_excel_csv_with_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $borrower = User::factory()->create(['role' => 'user', 'name' => 'Andi Pratama']);
        $proyektor = Item::factory()->create(['name' => 'Proyektor Kelas', 'code' => 'ELK-777']);
        $bola = Item::factory()->create(['name' => 'Bola Futsal', 'code' => 'OLR-888']);

        Loan::factory()->for($borrower)->for($proyektor)->create([
            'status' => 'dipinjam',
            'loan_date' => now()->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
        ]);
        Loan::factory()->for($borrower)->for($bola)->create([
            'status' => 'dikembalikan',
            'loan_date' => now()->subDays(5)->toDateString(),
            'due_date' => now()->subDays(2)->toDateString(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('loans.export.excel', ['status' => 'dipinjam']));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('Content-Disposition'));

        $xlsxContent = $response->streamedContent();
        $this->assertStringStartsWith('PK', $xlsxContent);
        $this->assertStringContainsString('Proyektor Kelas', $xlsxContent);
        $this->assertStringNotContainsString('Bola Futsal', $xlsxContent);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'export_loan',
        ]);
    }

    public function test_admin_can_open_pdf_print_report_and_non_admin_is_forbidden(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Petugas Cetak']);
        $user = User::factory()->create(['role' => 'user']);
        $item = Item::factory()->create(['name' => 'Mikroskop Laboratorium']);

        Loan::factory()->for($user)->for($item)->create(['status' => 'dipinjam']);

        $this->actingAs($user)
            ->get(route('loans.export.excel'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('loans.export.pdf'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('loans.export.pdf'))
            ->assertOk()
            ->assertSee('Laporan Rekapitulasi Peminjaman')
            ->assertSee('Mikroskop Laboratorium')
            ->assertSee('Petugas Cetak');
    }
}
