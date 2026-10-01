{{--
    Kop surat BEJO sesuai "BEJO Letterhead".

    Susunan: logo + nama PT + alamat/kontak di kiri, judul laporan + periode di
    kanan, garis pemisah, dan logo besar sebagai watermark di belakang konten.

    Parameter:
      $kopJudul      string  judul laporan
      $kopKanan      array   baris informasi periode (sisi kanan)
      $kopWatermark  bool    tampilkan logo besar di belakang (default: true)
--}}
@php
    $judul = $kopJudul ?? 'LAPORAN';
    $barisKanan = $kopKanan ?? [];
    $logo = $companyProfile->logoUrl();
    $pratinjau = $kopPratinjau ?? false;
@endphp

<style>
    .kop-surat {
        position: relative;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        padding-bottom: 8px;
        margin-bottom: 14px;
        border-bottom: 2.5px solid #1a1a1a;
    }
    .kop-surat__kiri {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }
    .kop-surat__logo {
        width: {{ $pratinjau ? '42px' : '62px' }};
        height: {{ $pratinjau ? '42px' : '62px' }};
        object-fit: contain;
        object-position: center;
        flex: 0 0 auto;
    }
    .kop-surat__nama {
        font-size: {{ $pratinjau ? '11pt' : '15pt' }};
        font-weight: 800;
        letter-spacing: 1.2px;
        color: #000;
        line-height: 1.1;
        margin: 0;
    }
    .kop-surat__kontak {
        font-size: {{ $pratinjau ? '6.5pt' : '8pt' }};
        color: #333;
        line-height: 1.45;
        margin: 3px 0 0;
    }
    .kop-surat__kanan {
        text-align: right;
        font-size: {{ $pratinjau ? '6.5pt' : '8.5pt' }};
        color: #333;
        line-height: 1.5;
        flex: 0 0 auto;
        max-width: 42%;
    }
    .kop-surat__judul {
        font-size: {{ $pratinjau ? '7.5pt' : '9.5pt' }};
        font-weight: 800;
        color: #000;
        margin: 0 0 4px;
        text-transform: uppercase;
    }
</style>

<div class="kop-surat">
    <div class="kop-surat__isi d-flex justify-content-between align-items-start gap-3" style="width: 100%;">
        <div class="kop-surat__kiri">
            @if($logo)
                <img src="{{ $logo }}" alt="Logo {{ $companyProfile->nama() }}" class="kop-surat__logo">
            @endif
            <div>
                <p class="kop-surat__nama">{{ $companyProfile->nama() }}</p>
                @foreach($companyProfile->kontakBaris() as $baris)
                    <p class="kop-surat__kontak">{{ $baris }}</p>
                @endforeach
            </div>
        </div>

        <div class="kop-surat__kanan">
            <p class="kop-surat__judul">{{ $judul }}</p>
            @foreach($barisKanan as $baris)
                <div>{!! $baris !!}</div>
            @endforeach
        </div>
    </div>
</div>
