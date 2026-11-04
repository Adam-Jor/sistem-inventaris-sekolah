<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Loan;
use App\Models\ReturnLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_statistics_with_top_items_and_borrowers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $budi = User::factory()->create(['role' => 'user', 'name' => 'Budi Peminjam Aktif']);
        $siti = User::factory()->create(['role' => 'user', 'name' => 'Siti Jarang Pinjam']);

        $proyektor = Item::factory()->create(['name' => 'Proyektor Favorit', 'stock' => 5]);
        $bola = Item::factory()->create(['name' => 'Bola Voli Cadangan', 'stock' => 1]);
        Item::factory()->create(['name' => 'Mikroskop Tak Tersentuh', 'stock' => 4]);

        // Budi meminjam proyektor 3x (satu terlambat dikembalikan), Siti meminjam bola 1x.
        $lateLoan = Loan::factory()->for($budi)->for($proyektor)->create([
            'status' => 'dikembalikan',
            'loan_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->subDays(7)->toDateString(),
        ]);
        ReturnLog::factory()->create(['loan_id' => $lateLoan->id, 'returned_at' => now()->subDays(5)->toDateString()]);

        Loan::factory()->for($budi)->for($proyektor)->create(['status' => 'dipinjam', 'loan_date' => now()->toDateString(), 'due_date' => now()->addDays(3)->toDateString()]);
        Loan::factory()->for($budi)->for($proyektor)->create(['status' => 'dipinjam', 'loan_date' => now()->toDateString(), 'due_date' => now()->addDays(3)->toDateString()]);
        Loan::factory()->for($siti)->for($bola)->create(['status' => 'dipinjam', 'loan_date' => now()->toDateString(), 'due_date' => now()->addDays(3)->toDateString()]);

        // Pengajuan ditolak tidak dihitung sebagai peminjaman barang.
        Loan::factory()->for($siti)->for($bola)->create(['status' => 'ditolak']);

        $response = $this->actingAs($admin)->get(route('statistics.index'));

        $response
            ->assertOk()
            ->assertSee('Barang Paling Sering Dipinjam')
            ->assertSee('Pengguna Paling Aktif')
            ->assertSeeInOrder(['Proyektor Favorit', 'Bola Voli Cadangan'])
            ->assertSeeInOrder(['Budi Peminjam Aktif', 'Siti Jarang Pinjam'])
            ->assertSee('Mikroskop Tak Tersentuh');

        $topItems = $response->viewData('topItems');
        $this->assertSame($proyektor->id, $topItems->first()->item_id);
        $this->assertSame(3, (int) $topItems->first()->loans_count);

        $topBorrowers = $response->viewData('topBorrowers');
        $this->assertSame($budi->id, $topBorrowers->first()->user_id);

        $lateBorrowers = $response->viewData('lateBorrowers');
        $this->assertCount(1, $lateBorrowers);
        $this->assertSame($budi->id, $lateBorrowers->first()->user_id);

        $summary = $response->viewData('summary');
        $this->assertSame(5, $summary['total_requests']);
        $this->assertSame(4, $summary['approved_count']);
        $this->assertSame(0.0, (float) $summary['on_time_rate']);
    }

    public function test_statistics_can_be_filtered_by_period(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $item = Item::factory()->create(['name' => 'Barang Lama Sekali']);

        Loan::factory()->for($item)->create([
            'status' => 'dipinjam',
            'loan_date' => now()->subDays(200)->toDateString(),
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($admin)
            ->get(route('statistics.index', ['periode' => '30']))
            ->assertOk()
            ->assertViewHas('period', '30')
            ->assertViewHas('topItems', fn ($items) => $items->isEmpty());

        $this->actingAs($admin)
            ->get(route('statistics.index', ['periode' => 'tidak-valid']))
            ->assertOk()
            ->assertViewHas('period', 'all')
            ->assertViewHas('topItems', fn ($items) => $items->count() === 1);
    }

    public function test_non_admin_cannot_access_statistics(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('statistics.index'))
            ->assertForbidden();
    }
}

