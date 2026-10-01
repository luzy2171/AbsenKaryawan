{{-- Favicon: pakai logo perusahaan, fallback ke favicon bawaan.
     Tampil di tab browser, sebelah kiri alamat "www..." --}}
<link rel="icon" href="{{ $companyProfile->faviconUrl() }}" type="{{ $companyProfile->faviconType() }}">
<link rel="apple-touch-icon" href="{{ $companyProfile->faviconUrl() }}">
