<?php

namespace App\Services;

use App\Models\MachineStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Backup & pulihkan seluruh kondisi aplikasi.
 *
 * Tiga bagian yang ditutup:
 *   - database  : dump SQL murni PHP (tidak butuh mysqldump di server)
 *   - files     : storage/app/public (logo perusahaan, dokumen cuti, dll)
 *   - machine   : daftar user + seluruh template sidik jari di mesin absensi
 *
 * Bagian "machine" penting karena tidak ada di database: kalau template rusak
 * di perangkat, satu-satunya sumber pemulihan adalah file backup ini.
 */
class BackupService
{
    public const DIR = 'backups';

    /**
     * Berapa banyak baris per INSERT untuk dump database.
     */
    protected int $chunk = 200;

    /**
     * Buat satu backup baru. Mengembalikan path folder backup.
     */
    public function create(bool $includeMachine = true, ?callable $log = null): string
    {
        $stempel = now()->format('Ymd-His');
        $dir = storage_path('app/'.self::DIR.'/'.$stempel);

        File::ensureDirectoryExists($dir);

        $catatan = $this->log($log, 'Mencadangkan database...');
        $this->dumpDatabase($dir.'/database.sql');

        $catatan = $this->log($log, 'Mencadangkan file upload...');
        $this->arsipkanFilePublic($dir);

        $mesin = ['diambil' => false, 'catatan' => ' dilewati (--tanpa-mesin)'];
        if ($includeMachine) {
            $catatan = $this->log($log, 'Mengambil data mesin absensi...');
            $mesin = $this->snapshotMesin($dir, $log);
        }

        $manifest = [
            'dibuat_pada' => now()->toIso8601String(),
            'aplikasi' => config('app.name'),
            'server' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'host' => gethostname(),
            ],
            'database' => config('database.default'),
            'file_ukuran' => $this->ukuranFolder($dir),
            'machine' => $mesin,
        ];

        File::put($dir.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->log($log, "Backup selesai: {$dir}");

        return $dir;
    }

    /**
     * Daftar backup yang tersedia, terbaru lebih dulu.
     *
     * @return array<int, array<string, mixed>>
     */
    public function daftar(): array
    {
        $root = storage_path('app/'.self::DIR);
        if (! is_dir($root)) {
            return [];
        }

        $hasil = [];

        foreach (File::directories($root) as $dir) {
            $manifest = $dir.'/manifest.json';
            $stempel = basename($dir);

            $hasil[] = [
                'id' => $stempel,
                'lokasi' => $dir,
                'ada_db' => is_file($dir.'/database.sql'),
                'ada_file' => is_file($dir.'/files-public.tar.gz'),
                'ada_mesin' => is_file($dir.'/machine.json'),
                'dibuat' => is_file($manifest) ? (json_decode(File::get($manifest), true)['dibuat_pada'] ?? null) : null,
                'ukuran' => $this->ukuranFolder($dir),
            ];
        }

        usort($hasil, static fn ($a, $b) => strcmp($b['id'], $a['id']));

        return $hasil;
    }

    public function find(string $id): string
    {
        $dir = storage_path('app/'.self::DIR.'/'.basename($id));

        if (! is_dir($dir)) {
            throw new RuntimeException("Backup '{$id}' tidak ditemukan.");
        }

        return $dir;
    }

    /**
     * Hapus satu backup.
     */
    public function hapus(string $id): void
    {
        File::deleteDirectory($this->find($id));
    }

    // ------------------------------------------------------------------
    // Pemulihan
    // ------------------------------------------------------------------

    /**
     * Pulihkan database dari file SQL.
     *
     * Memakai transactions=false karena dump berisi CREATE TABLE, dan tabel
     * perlu dikosongkan dulu sebelum INSERT.
     */
    public function pulihkanDatabase(string $id, bool $truncate = true, ?callable $log = null): void
    {
        $file = $this->find($id).'/database.sql';

        if (! is_file($file)) {
            throw new RuntimeException("Backup '{$id}' tidak punya database.sql.");
        }

        $this->log($log, 'Membaca dump database...');
        $sql = File::get($file);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            if ($truncate) {
                $this->log($log, 'Mengosongkan tabel lama...');
                foreach (array_reverse($this->daftarTabel()) as $tabel) {
                    DB::statement("TRUNCATE TABLE `{$tabel}`");
                }
            }

            $this->log($log, 'Menjalankan dump...');
            foreach ($this->pecahPerintah($sql) as $perintah) {
                DB::unprepared($perintah);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->log($log, 'Database dipulihkan.');
    }

    /**
     * Pulihkan file upload dari arsip.
     */
    public function pulihkanFile(string $id, ?callable $log = null): void
    {
        $arsip = $this->find($id).'/files-public.tar.gz';

        if (! is_file($arsip)) {
            throw new RuntimeException("Backup '{$id}' tidak punya files-public.tar.gz.");
        }

        $tujuan = storage_path('app/public');

        $this->log($log, 'Mengganti file upload...');
        File::deleteDirectory($tujuan);
        File::ensureDirectoryExists($tujuan);

        // PharData mengenali kompresi otomatis dari isi file.
        $phar = new \PharData($arsip);
        $phar->extractTo($tujuan, null, true);
        $this->log($log, "File upload dipulihkan ke {$tujuan}.");
    }

    /**
     * Pulihkan user + template sidik jari ke mesin absensi.
     *
     * PERINGATAN: ini menulis ke perangkat keras. Selalu cek daftar mesin
     * lebih dulu dan pastikan memilih mesin yang benar.
     *
     * @param  array<int, array<string, mixed>>  $override  untuk mengoreksi
     *                                                      nama/pin hasil mapping
     * @return array<string, int>
     */
    public function pulihkanMesin(string $id, SolutionSoapService $layanan, array $override = [], ?callable $log = null): array
    {
        $file = $this->find($id).'/machine.json';

        if (! is_file($file)) {
            throw new RuntimeException("Backup '{$id}' tidak punya machine.json.");
        }

        $snap = json_decode(File::get($file), true);

        if (empty($snap['templates'])) {
            return ['user' => 0, 'template' => 0, 'gagal' => 0, 'tanpa_nama' => []];
        }

        $pin = (string) ($snap['pin'] ?? '0');
        $hasil = ['user' => 0, 'template' => 0, 'gagal' => 0, 'tanpa_nama' => []];

        $grup = [];
        foreach ($snap['templates'] as $t) {
            $grup[(int) $t['user_id']][] = $t;
        }

        $nama = $snap['nama_user'] ?? [];

        foreach ($grup as $userId => $templates) {
            $label = trim((string) ($override[$userId]['nama'] ?? $nama[$userId] ?? ''));
            $sumberNama = 'backup';

            // Kalau nama kosong di backup, ambil dari database lokal. Ini yang
            // paling sering terjadi: user dibuat di mesin tanpa nama, lalu
            // datanya rusak - nama aslinya masih ada di tabel karyawans.
            if ($label === '') {
                $label = $this->namaKaryawanLokal((int) $userId);
                $sumberNama = 'db lokal';
            }

            if ($label === '') {
                $this->log($log, "Lewati user {$userId}: nama tidak ada di backup maupun db lokal.");
                $hasil['gagal']++;
                $hasil['tanpa_nama'][] = $userId;

                continue;
            }

            $this->log($log, "Kirim user {$userId} = {$label} (dari {$sumberNama}) ke mesin...");
            $layanan->setUserInfo($userId, $label);

            foreach ($templates as $t) {
                $template = (string) ($override[$userId]['template'][$t['finger_id']] ?? base64_decode($t['b64'], true));
                $res = $layanan->setUserTemplate((string) $userId, (int) $t['finger_id'], $template);

                if ($res === 'Sukses') {
                    $hasil['template']++;
                    $this->log($log, "  slot {$t['finger_id']} OK");
                } else {
                    $hasil['gagal']++;
                    $this->log($log, "  slot {$t['finger_id']} GAGAL: {$res}");
                }
            }

            $hasil['user']++;
        }

        $layanan->refreshDatabase();
        $this->log($log, "Selesai: {$hasil['user']} user, {$hasil['template']} template, {$hasil['gagal']} gagal.");

        if (! empty($hasil['tanpa_nama'])) {
            $this->log($log, 'User tanpa nama (tidak ada di backup maupun db lokal): '
                .implode(', ', $hasil['tanpa_nama']));
        }

        return $hasil;
    }

    /**
     * Nama karyawan dari database lokal berdasarkan id_karyawan.
     */
    protected function namaKaryawanLokal(int $userId): string
    {
        try {
            $nama = DB::table('karyawans')
                ->where('id_karyawan', $userId)
                ->value('nama');

            return is_string($nama) ? trim($nama) : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    // ------------------------------------------------------------------
    // Penulis
    // ------------------------------------------------------------------

    /**
     * Dump seluruh tabel ke file SQL memakai PDO (tanpa mysqldump).
     */
    protected function dumpDatabase(string $file): void
    {
        $pdo = DB::connection()->getPdo();
        $handle = fopen($file, 'wb');

        if ($handle === false) {
            throw new RuntimeException("Tidak bisa menulis {$file}");
        }

        $header = '-- Backup database '.config('database.connections.mysql.database')."\n"
            .'-- Dibuat '.now()->toIso8601String()."\n"
            ."SET NAMES utf8mb4;\n"
            ."SET FOREIGN_KEY_CHECKS=0;\n\n";

        fwrite($handle, $header);

        foreach ($this->daftarTabel() as $tabel) {
            $create = $pdo->query("SHOW CREATE TABLE `{$tabel}`")->fetch(PDO::FETCH_ASSOC);
            fwrite($handle, "DROP TABLE IF EXISTS `{$tabel}`;\n");
            fwrite($handle, ($create['Create Table'] ?? '').";\n\n");

            $total = 0;
            $kolom = null;
            $tuple = [];

            $query = DB::table($tabel);
            $kolomPK = $this->kolomPrimaryKey($tabel);

            if ($kolomPK !== null) {
                $query->orderBy($kolomPK);
            }

            foreach ($query->cursor() as $row) {
                $row = (array) $row;
                $kolom ??= $this->daftarKolom($tabel);
                $tuple[] = '('.implode(', ', array_map(fn ($v) => $this->kutip($v), array_values($row))).')';

                if (count($tuple) >= $this->chunk) {
                    $this->tulisInsert($handle, $tabel, $kolom, $tuple);
                    $total += count($tuple);
                    $tuple = [];
                }
            }

            if ($tuple !== []) {
                $this->tulisInsert($handle, $tabel, $kolom, $tuple);
                $total += count($tuple);
            }

            fwrite($handle, "\n-- {$tabel}: {$total} baris\n\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
    }

    /**
     * Tulis satu INSERT multi-baris: VALUES (a),(b),(c);
     *
     * @param  array<int, string>  $tuple
     */
    protected function tulisInsert($handle, string $tabel, array $kolom, array $tuple): void
    {
        $daftarKolom = implode(', ', array_map(static fn ($c) => "`{$c}`", $kolom));

        fwrite(
            $handle,
            'INSERT INTO `'.$tabel.'` ('.$daftarKolom.") VALUES\n"
            .implode(",\n", $tuple).";\n"
        );
    }

    /**
     * @return array<int, string>
     */
    protected function daftarKolom(string $tabel): array
    {
        $kolom = [];

        foreach (DB::select("SHOW COLUMNS FROM `{$tabel}`") as $baris) {
            $nilai = (array) $baris;
            $kolom[] = (string) ($nilai['Field'] ?? '');
        }

        return $kolom;
    }

    /**
     * Kolom primary key sebuah tabel, atau null bila tabel tanpa primary key.
     */
    protected function kolomPrimaryKey(string $tabel): ?string
    {
        try {
            $baris = DB::select("SHOW KEYS FROM `{$tabel}` WHERE Key_name = 'PRIMARY'");

            if ($baris === []) {
                return null;
            }

            $nilai = (array) $baris[0];

            return isset($nilai['Column_name']) ? (string) $nilai['Column_name'] : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Kutip nilai sesuai aturan SQL. Biner ditulis sebagai hex agar aman.
     */
    protected function kutip($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $value = (string) $value;

        // Deteksi data biner (ada byte kontrol) -> pakai X'..'
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value) === 1) {
            return "X'".bin2hex($value)."'";
        }

        return DB::connection()->getPdo()->quote($value);
    }

    /**
     * Arsipkan storage/app/public (file logo, dokumen, dll).
     *
     * phar.readonly=1 di server ini membuat flag kompresi PharData diabaikan,
     * jadi tar dibuat polos lalu dikompresi manual ke .tar.gz.
     */
    protected function arsipkanFilePublic(string $dir): void
    {
        $sumber = storage_path('app/public');
        $sementara = $dir.'/files-public.tar';
        $arsip = $dir.'/files-public.tar.gz';

        if (! is_dir($sumber)) {
            File::put($arsip, '');

            return;
        }

        $tar = new \PharData($sementara, 0, 'backup');
        $tar->buildFromDirectory($sumber);
        unset($tar);

        $mentah = File::get($sementara);
        File::put($arsip, (string) gzencode($mentah, 9));
        File::delete($sementara);
    }

    /**
     * Ambil daftar user + seluruh template dari mesin absensi.
     *
     * @return array<string, mixed>
     */
    protected function snapshotMesin(string $dir, ?callable $log = null): array
    {
        $snap = [
            'diambil_pada' => now()->toIso8601String(),
            'machines' => [],
        ];

        $machines = MachineStatus::whereIn('machine_type', ['solution', 'x100c'])->get();

        foreach ($machines as $machine) {
            // Mesin Solution bicara SOAP di port 80; kolom port pada mesin
            // biasanya berisi 4370 (port protokol biner) jadi tidak boleh
            // dipakai di sini.
            $port = (int) (env('SOLUTION_SOAP_PORT', 80) ?: 80);

            $layanan = new SolutionSoapService;
            $layanan->setConnection($machine->machine_ip, $port, (string) $machine->username);

            $users = $layanan->getAllUsers();
            $pin = (string) $machine->username;
            if (preg_match('/^\d{1,10}$/', $pin) !== 1) {
                $pin = '0';
            }

            $entry = [
                'id' => $machine->id,
                'nama' => $machine->machine_name,
                'ip' => $machine->machine_ip,
                'port' => $port,
                'commkey' => $pin,
                'terhubung' => $users !== [],
                'users' => [],
                'nama_user' => [],
                'templates' => [],
            ];

            if ($users === []) {
                $entry['catatan'] = 'Mesin tidak merespons GetAllUserInfo';
                $snap['machines'][] = $entry;
                $this->log($log, "  {$machine->machine_name}: tidak merespons.");

                continue;
            }

            foreach ($users as $u) {
                $userId = trim((string) ($u['pin2'] ?? '')) ?: (string) $u['pin'];
                $entry['users'][] = ['user_id' => $userId, 'record' => $u['pin'], 'nama' => $u['name']];
                $entry['nama_user'][$userId] = $u['name'];
            }

            foreach ($entry['users'] as $u) {
                for ($f = 0; $f <= 9; $f++) {
                    $t = $layanan->getUserTemplate($u['user_id'], $f);
                    if ($t === '') {
                        continue;
                    }
                    $entry['templates'][] = [
                        'user_id' => $u['user_id'],
                        'record' => $u['record'],
                        'finger_id' => $f,
                        'b64' => $t,
                        'b64_len' => strlen($t),
                    ];
                }
            }

            $this->log($log, "  {$machine->machine_name}: ".count($entry['users'])
                .' user, '.count($entry['templates']).' template');

            $snap['machines'][] = $entry;
        }

        File::put(
            $dir.'/machine.json',
            json_encode($snap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $total = array_sum(array_map(static fn ($m) => count($m['templates']), $snap['machines']));

        return ['diambil' => true, 'template' => $total, 'mesin' => count($snap['machines'])];
    }

    // ------------------------------------------------------------------
    // Util
    // ------------------------------------------------------------------

    /**
     * @return array<int, string>
     */
    protected function daftarTabel(): array
    {
        $nilai = DB::select('SHOW TABLES');
        $tabel = [];

        foreach ($nilai as $row) {
            $nilaiSatu = (array) $row;
            $tabel[] = (string) reset($nilaiSatu);
        }

        return $tabel;
    }

    /**
     * Pecah file SQL menjadistatement per blok (dibatasi ; di akhir baris).
     *
     * @return array<int, string>
     */
    protected function pecahPerintah(string $sql): array
    {
        $perintah = [];
        $buffer = '';
        $panjang = strlen($sql);

        for ($i = 0; $i < $panjang; $i++) {
            $c = $sql[$i];
            $buffer .= $c;

            // Abaikan ; di dalam string
            if ($c === "'") {
                for ($i++; $i < $panjang; $i++) {
                    $buffer .= $sql[$i];
                    if ($sql[$i] === '\\') {
                        $i++;
                        if ($i < $panjang) {
                            $buffer .= $sql[$i];
                        }

                        continue;
                    }
                    if ($sql[$i] === "'") {
                        break;
                    }
                }

                continue;
            }

            if ($c === ';') {
                $bersih = trim($buffer);
                if ($bersih !== '') {
                    $perintah[] = $bersih;
                }
                $buffer = '';
            }
        }

        $sisa = trim($buffer);
        if ($sisa !== '') {
            $perintah[] = $sisa;
        }

        return $perintah;
    }

    protected function ukuranFolder(string $dir): int
    {
        $total = 0;

        foreach (File::allFiles($dir) as $f) {
            $total += $f->getSize();
        }

        return $total;
    }

    protected function log(?callable $log, string $pesan): ?string
    {
        if ($log) {
            $log($pesan);
        }

        return $pesan;
    }
}
