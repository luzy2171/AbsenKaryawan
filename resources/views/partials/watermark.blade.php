{{--
    Logo besar sebagai watermark di belakang konten, seperti di letterhead asli.

    Dipakai sekali per halaman, di dalam elemen container yang posisinya relative
    (mis. wrapper halaman cetak), agar berada di tengah badan dokumen - bukan
    menumpuk di atas kop.
--}}
@if($companyProfile->logoUrl())
    <img src="{{ $companyProfile->logoUrl() }}" alt="" class="watermark-perusahaan">
    <style>
        .watermark-perusahaan {
            position: absolute;
            top: 52%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 46%;
            max-width: 340px;
            opacity: 0.06;
            pointer-events: none;
            z-index: 0;
        }
    </style>
@endif
