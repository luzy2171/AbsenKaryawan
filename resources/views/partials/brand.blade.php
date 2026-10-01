{{-- Logo bulat + nama perusahaan, dipakai di seluruh sidebar.
     width/height ditulis inline supaya tampilan tetap aman even kalau
     custom.css masih versi lama di cache browser. --}}
<div class="d-flex align-items-center mb-3 px-2 py-2 brand-block">
    @if($companyProfile->logoUrl())
        <img src="{{ $companyProfile->logoUrl() }}" alt="Logo {{ $companyProfile->nama() }}"
             class="brand-logo me-3" width="64" height="64">
    @else
        <div class="stat-icon bg-success text-white me-3 brand-logo-fallback">
            <i class="bi bi-fingerprint"></i>
        </div>
    @endif
    <div class="brand-text">
        <h5 class="fw-bold m-0 text-success brand-title">{{ $companyProfile->nama() }}</h5>
        <small class="text-muted brand-subtitle">{{ $companyProfile->get('subtitle', \App\Helpers\CompanyProfile::DEFAULT_SUBTITLE) }}</small>
    </div>
</div>
