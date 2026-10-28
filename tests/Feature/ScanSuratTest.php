<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\ScanSurat;
use App\Models\Surat;
use App\Models\User;
use App\Services\Ocr\HasilOcr;
use App\Services\Ocr\PembacaOcr;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class ScanSuratTest extends TestCase
{
    use DatabaseTransactions;

    private User $staff;

    private User $kasubag;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $roleStaff = Role::firstOrCreate(['nama_role' => 'staff_umum']);
        $roleKasubag = Role::firstOrCreate(['nama_role' => 'kasubag_umum']);
        Role::firstOrCreate(['nama_role' => 'kabag_umum']);
        Role::firstOrCreate(['nama_role' => 'direktur']);
        Role::firstOrCreate(['nama_role' => 'admin']);

        $this->staff = User::create([
            'role_id' => $roleStaff->id,
            'nama' => 'Staff Scan',
            'email' => 'scan-staff-'.Str::random(8).'@example.test',
            'password' => bcrypt('password'),
        ]);

        $this->kasubag = User::create([
            'role_id' => $roleKasubag->id,
            'nama' => 'Kasubag Scan',
            'email' => 'scan-kasubag-'.Str::random(8).'@example.test',
            'password' => bcrypt('password'),
        ]);

        $this->app->instance(PembacaOcr::class, $this->pembacaPalsu());
    }

    public function test_hasil_scan_disimpan_sebagai_lampiran_saat_surat_dibuat(): void
    {
        $respons = $this->actingAs($this->staff)->post(route('surat.scan'), [
            'berkas' => $this->gambarPalsu(),
            'arah_surat' => 'masuk',
        ]);

        $respons->assertOk();
        $respons->assertJsonPath('berhasil', true);
        $respons->assertJsonPath('fields.nomor_surat.value', '421/123/DISDIK/2026');
        $respons->assertJsonPath('fields.nomor_agenda.value', '1247/SM/IX/2026');
        $respons->assertJsonPath('fields.tanggal_surat.value', '2026-10-12');
        $respons->assertJsonPath('fields.tanggal_diterima.value', now()->toDateString());
        $respons->assertJsonPath('fields.arah_surat.value', 'masuk');

        $token = $respons->json('token');
        $scan = ScanSurat::where('token', $token)->firstOrFail();

        $this->assertSame('selesai', $scan->status);
        Storage::disk('local')->assertExists($scan->path_file);

        $simpan = $this->actingAs($this->staff)->post(route('surat.store'), [
            'arah_surat' => 'masuk',
            'jenis_surat' => 'Surat Undangan',
            'nomor_surat' => '421/123/DISDIK/2026',
            'tanggal_surat' => '2026-10-12',
            'tanggal_diterima' => now()->subDays(2)->toDateString(),
            'surat_dari' => 'Dinas Pendidikan Kabupaten Magelang',
            'perihal' => 'Undangan Rapat Koordinasi',
            'prioritas' => 'biasa',
            'scan_token' => $token,
        ]);

        $simpan->assertRedirect();

        $surat = Surat::where('created_by', $this->staff->id)->firstOrFail();

        $simpan->assertRedirect(route('surat.show', $surat));

        $dispoAwal = $surat->disposisi()->firstOrFail();
        $this->assertSame(
            now()->subDays(2)->addDays(7)->toDateString(),
            $dispoAwal->batas_waktu->toDateString()
        );

        // Kalender surat masuk memakai tanggal diterima, bukan tanggal surat.
        $kalender = $this->actingAs($this->staff)->get(route('surat.index', [
            'bulan' => now()->subDays(2)->format('Y-m'),
        ]));
        $kalender->assertOk();
        $this->assertTrue(
            $kalender->viewData('tanggalBersurat')->contains(now()->subDays(2)->toDateString())
        );

        $filterTanggal = $this->actingAs($this->staff)->get(route('surat.index', [
            'tanggal' => now()->subDays(2)->toDateString(),
        ]));
        $filterTanggal->assertOk();
        $this->assertTrue(
            $filterTanggal->viewData('surat')->pluck('id')->contains($surat->id)
        );

        $lampiran = $surat->lampiran()->firstOrFail();

        $this->assertSame('surat-scan.png', $lampiran->nama_file);
        Storage::disk('public')->assertExists($lampiran->path_file);

        $scan->refresh();
        $this->assertNotNull($scan->dipakai_at);
        Storage::disk('local')->assertMissing($scan->path_file);
    }

    public function test_tanggal_diterima_tetap_diisi_walau_ocr_gagal_untuk_surat_masuk(): void
    {
        $this->app->instance(PembacaOcr::class, new class implements PembacaOcr
        {
            public function baca(string $pathAbsolut, string $mime): HasilOcr
            {
                throw new RuntimeException('OCR tidak tersedia.');
            }
        });

        $respons = $this->actingAs($this->staff)->post(route('surat.scan'), [
            'berkas' => $this->gambarPalsu(),
            'arah_surat' => 'masuk',
        ]);

        $respons->assertOk();
        $respons->assertJsonPath('berhasil', false);
        $respons->assertJsonPath('fields.tanggal_diterima.value', now()->toDateString());
    }

    public function test_token_scan_kedaluwarsa_ditolak_dan_surat_tidak_dibuat(): void
    {
        $scan = ScanSurat::create([
            'user_id' => $this->staff->id,
            'token' => (string) Str::uuid(),
            'nama_file' => 'lama.png',
            'path_file' => 'scan-surat/'.$this->staff->id.'/lama.png',
            'tipe_file' => 'image/png',
            'ukuran_file' => 10,
            'status' => 'selesai',
            'hasil_json' => ['fields' => [], 'peringatan' => []],
            'expires_at' => now()->subMinute(),
        ]);

        $simpan = $this->actingAs($this->staff)->post(route('surat.store'), [
            'arah_surat' => 'keluar',
            'jenis_surat' => 'Surat Biasa',
            'nomor_surat' => '1/PDAM/2026',
            'tanggal_surat' => '2026-10-12',
            'tujuan_surat' => 'PT Contoh',
            'perihal' => 'Surat Uji',
            'scan_token' => $scan->token,
        ]);

        $simpan->assertSessionHasErrors('scan_token');
        $this->assertSame(0, Surat::where('created_by', $this->staff->id)->count());
        $this->assertNull($scan->refresh()->dipakai_at);
    }

    public function test_role_selain_staff_tidak_boleh_memindai(): void
    {
        $respons = $this->actingAs($this->kasubag)->post(route('surat.scan'), [
            'berkas' => $this->gambarPalsu(),
        ]);

        $respons->assertForbidden();
        $this->assertSame(0, ScanSurat::where('user_id', $this->kasubag->id)->count());
    }

    public function test_form_memulihkan_lampiran_scan_setelah_validasi_gagal(): void
    {
        $scan = ScanSurat::create([
            'user_id' => $this->staff->id,
            'token' => (string) Str::uuid(),
            'nama_file' => 'scan-tersimpan.png',
            'path_file' => 'scan-surat/'.$this->staff->id.'/scan-tersimpan.png',
            'tipe_file' => 'image/png',
            'ukuran_file' => 4,
            'status' => 'selesai',
            'hasil_json' => ['fields' => [], 'peringatan' => []],
            'expires_at' => now()->addMinutes(30),
        ]);

        Storage::disk('local')->put($scan->path_file, 'isi');

        $this->from(route('surat.create'))
            ->actingAs($this->staff)
            ->post(route('surat.store'), [
                'arah_surat' => 'masuk',
                'jenis_surat' => 'Surat Biasa',
                'nomor_surat' => '1/A/2026',
                'tanggal_surat' => '2026-10-12',
                'prioritas' => 'biasa',
                'scan_token' => $scan->token,
            ])
            ->assertSessionHasErrors(['surat_dari', 'perihal']);

        $this->actingAs($this->staff)
            ->get(route('surat.create'))
            ->assertOk()
            ->assertSee('Scan Surat')
            ->assertSee('scan-tersimpan.png');
    }

    private function gambarPalsu(): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );

        return UploadedFile::fake()->createWithContent('surat-scan.png', $png);
    }

    private function pembacaPalsu(): PembacaOcr
    {
        return new class implements PembacaOcr
        {
            public function baca(string $pathAbsolut, string $mime): HasilOcr
            {
                $baris = [
                    'PEMERINTAH KABUPATEN MAGELANG',
                    'DINAS PENDIDIKAN',
                    'Jl. Soekarno Hatta No. 12, Magelang',
                    'Nomor: 421/123/DISDIK/2026',
                    'Nomor Agenda: 1247/SM/IX/2026',
                    'Tanggal: 12 Oktober 2026',
                    'Perihal: Undangan Rapat Koordinasi',
                    'Kepada Yth. Direktur Perumda Tirta Gemilang',
                    'di tempat',
                ];

                $data = [];

                foreach ($baris as $i => $teks) {
                    $data[] = [
                        'teks' => $teks,
                        'confidence' => 0.95,
                        'top' => $i * 20,
                        'bottom' => ($i * 20) + 15,
                    ];
                }

                return new HasilOcr(implode("\n", $baris), $data, 0.95);
            }
        };
    }
}
