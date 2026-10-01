<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Surat {{ $disetujui ? 'Persetujuan' : 'Penolakan' }} {{ $leave->jenis }} - {{ $leave->karyawan->nama }}</title>
    <style>
        @page { margin: 22mm 18mm 18mm 18mm; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
            margin: 0;
        }

        /* ---------- Kop surat ---------- */
        .kop { width: 100%; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 18px; }
        .kop td { vertical-align: middle; }
        .kop .logo { width: 78px; }
        .kop .logo img { max-width: 78px; max-height: 78px; }
        .kop-badan { text-align: center; }
        .kop-badan .nama { font-size: 15pt; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; line-height: 1.2; }
        .kop-badan .sub { font-size: 10pt; font-style: italic; }
        .kop-badan .kontak { font-size: 8.5pt; color: #222; }

        /* ---------- Judul surat ---------- */
        .judul { text-align: center; margin-bottom: 18px; }
        .judul .no { font-size: 10.5pt; margin-bottom: 3px; }
        .judul h1 {
            font-size: 14pt; text-transform: uppercase; letter-spacing: 1px;
            text-decoration: underline; margin: 0 0 12px 0;
        }

        /* ---------- Isi ---------- */
        .table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .table td { padding: 2.5px 0; vertical-align: top; font-size: 10.5pt; }
        .table td.label { width: 160px; }

        .paragraf { text-align: justify; margin: 0 0 12px 0; }

        .kotak { border: 1px solid #000; padding: 9px 11px; margin-bottom: 14px; font-size: 10.5pt; }
        .kotak .judul-kotak { font-weight: bold; margin-bottom: 4px; }
        .kotak .isi { font-style: italic; }

        .resolusi { border: 2px solid #000; padding: 10px 12px; font-weight: bold; text-align: center; font-size: 11pt; }

        /* ---------- Tanda tangan ---------- */
        .ttd-teks { text-align: center; font-size: 10.5pt; }
        .ttd-tabel { width: 100%; margin-top: 22px; border-collapse: collapse; }
        .ttd-tabel td { vertical-align: top; text-align: center; padding: 0 4px; }
        .ttd-ruang { height: 62px; }
        .ttd-ruang img { max-height: 58px; max-width: 150px; }
        .ttd-nama { font-weight: bold; text-decoration: underline; font-size: 10pt; }
        .ttd-jabatan { font-size: 9.5pt; }

        /* ---------- Lampiran ---------- */
        .lampiran { page-break-before: always; }
        .lampiran-judul { text-align: center; font-weight: bold; text-decoration: underline; margin-bottom: 14px; }
        .lampiran-gambar { text-align: center; }
        .lampiran-gambar img { max-width: 100%; }

        .catatan { font-size: 8.5pt; color: #444; margin-top: 14px; border-top: 1px solid #ccc; padding-top: 6px; }
    </style>
    @php
        // dompdf hanya menyediakan font DejaVu (tanpa locale), jadi nama bulan
        // diterjemahkan manual agar surat terbaca dalam bahasa Indonesia.
        $bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $tgl = fn ($carbon) => $carbon->format('d') . ' ' . $bulan[(int) $carbon->format('n') - 1] . ' ' . $carbon->format('Y');
    @endphp
</head>
<body>

@php
    $logo = \App\Helpers\CompanyProfile::logoDataUri();
    $waktuKeputusan = $leave->waktuKeputusan();
    $tanggalKeputusan = $waktuKeputusan ? $tgl($waktuKeputusan) : '-';
    $jamKeputusan = $waktuKeputusan ? $waktuKeputusan->format('H:i') : '-';
@endphp

<table class="kop">
    <tr>
        <td class="logo">
            @if ($logo)
                <img src="{{ $logo }}" alt="Logo">
            @endif
        </td>
        <td class="kop-badan">
            <div class="nama">{{ \App\Helpers\CompanyProfile::nama() }}</div>
            @if (\App\Helpers\CompanyProfile::get('subtitle'))
                <div class="sub">{{ \App\Helpers\CompanyProfile::get('subtitle') }}</div>
            @endif
            @foreach (\App\Helpers\CompanyProfile::kontakBaris() as $baris)
                <div class="kontak">{{ $baris }}</div>
            @endforeach
        </td>
    </tr>
</table>

<div class="judul">
    <div class="no">Nomor : {{ $nomorSurat }}</div>
    <h1>Surat {{ $disetujui ? 'Persetujuan' : 'Penolakan' }} {{ $leave->jenis }}</h1>
</div>

<table class="table">
    <tr>
        <td class="label">Nama</td>
        <td>: {{ $leave->karyawan->nama }}</td>
    </tr>
    <tr>
        <td class="label">ID Karyawan</td>
        <td>: {{ $leave->karyawan->id_karyawan ?? '-' }}</td>
    </tr>
    <tr>
        <td class="label">Departemen</td>
        <td>: {{ $leave->karyawan->departemen ?: '-' }}</td>
    </tr>
    <tr>
        <td class="label">Jabatan</td>
        <td>: {{ $leave->karyawan->jabatan ?: '-' }}</td>
    </tr>
    <tr>
        <td class="label">Jenis Pengajuan</td>
        <td>: {{ $leave->jenis }}</td>
    </tr>
    <tr>
        <td class="label">Tanggal</td>
        <td>: {{ $tgl($leave->tanggal_mulai) }} s.d. {{ $tgl($leave->tanggal_selesai) }}</td>
    </tr>
    <tr>
        <td class="label">Jumlah</td>
        <td>: {{ $leave->jumlahHari() }} hari kerja</td>
    </tr>
</table>

<p class="paragraf">
    Sehubungan dengan pengajuan {{ $leave->jenis }} yang diajukan oleh
    <strong>{{ $leave->karyawan->nama }}</strong>, dengan ini menyatakan bahwa pengajuan tersebut
    <strong>{{ $disetujui ? 'DAPAT DISETUJUI' : 'TIDAK DAPAT DISETUJUI' }}</strong>
    dengan alasan dan keterangan sebagai berikut:
</p>

<div class="kotak">
    <div class="judul-kotak">Alasan / Keterangan</div>
    <div class="isi">
        {{ $disetujui
            ? ($leave->keterangan ?: 'Pengajuan sesuai dengan ketentuan yang berlaku.')
            : ($leave->alasan_tolak ?: 'Tidak ada keterangan tambahan.') }}
    </div>
</div>

<div class="resolusi">
    {{ $disetujui
        ? 'Pengajuan ini DISETUJUI dan dapat digunakan sebagai dasar ' . strtolower($leave->jenis) . '.'
        : 'Pengajuan ini DITOLAK dan tidak dapat digunakan sebagai dasar ' . strtolower($leave->jenis) . '.' }}
</div>

{{-- Tanda tangan: semua approver yang sudah menyetujui, atau penolak untuk surat penolakan. --}}
<table class="ttd-tabel">
    <tr>
        <td colspan="{{ max(1, count($penyetuju)) }}"></td>
    </tr>
    <tr>
        <td class="ttd-teks">
            {{ \App\Helpers\CompanyProfile::nama() }},
            <br>
            {{ $tanggalKeputusan }}
        </td>
    </tr>
    <tr>
        @foreach ($penyetuju as $p)
            <td>
                <div class="ttd-ruang">
                    @if ($p['signature'])
                        <img src="{{ $p['signature'] }}" alt="Tanda tangan">
                    @endif
                </div>
                <div class="ttd-nama">{{ $p['nama'] }}</div>
                <div class="ttd-jabatan">{{ $p['jabatan'] }}</div>
            </td>
        @endforeach
        @if (count($penyetuju) === 0)
            <td>
                <div class="ttd-ruang"></div>
                <div class="ttd-nama">{{ $penyetujuFallback ?? '-' }}</div>
                <div class="ttd-jabatan">{{ $jabatanFallback ?? '-' }}</div>
            </td>
        @endif
    </tr>
</table>

@if ($leave->dokumen && $lampiranGambar)
    <div class="lampiran">
        <div class="lampiran-judul">Lampiran : {{ $leave->namaLampiran() }}</div>
        <div class="lampiran-gambar">
            <img src="{{ $lampiranGambar }}" alt="Lampiran {{ $leave->jenis }}">
        </div>
    </div>
@elseif ($leave->dokumen)
    <div class="lampiran">
        <div class="lampiran-judul">Lampiran</div>
        <p style="text-align: justify;">
            Pengajuan ini dilampirkan dokumen pendukung bernama
            <strong>{{ $leave->namaLampiran() }}</strong>.
            Dokumen tersebut tidak dilampirkan secara elektronik pada surat ini dan
            dapat dilihat pada sistem.
        </p>
    </div>
@endif

<p class="catatan">
    Surat ini dihasilkan otomatis oleh sistem Absensi BBM pada
    {{ $tgl(now()) }} pukul {{ now()->format('H:i') }} WIB.
    @if ($waktuKeputusan)
        Keputusan dicetak pada {{ $tanggalKeputusan }} pukul {{ $jamKeputusan }} WIB.
    @endif
</p>

</body>
</html>