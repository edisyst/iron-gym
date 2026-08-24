<div>
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $activeMembersCount }}</h3>
                    <p>Tesserati attivi</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
                <a href="{{ route('backoffice.members.index') }}" class="small-box-footer">
                    Vedi tutti <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $expiringSoonCount }}</h3>
                    <p>Abbonamenti in scadenza (30gg)</p>
                </div>
                <div class="icon"><i class="fas fa-id-card"></i></div>
                <a href="{{ route('backoffice.members.expiry') }}" class="small-box-footer">
                    Vedi scadenze <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        @can('view-access-logs')
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $accessesTodayCount }}</h3>
                    <p>Accessi oggi</p>
                </div>
                <div class="icon"><i class="fas fa-door-open"></i></div>
                <a href="{{ route('backoffice.access-logs.index') }}" class="small-box-footer">
                    Vedi registro <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        @endcan
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $medicalCertIssuesCount }}</h3>
                    <p>Certificati scaduti / in scadenza</p>
                </div>
                <div class="icon"><i class="fas fa-file-medical"></i></div>
                <a href="{{ route('backoffice.members.expiry') }}" class="small-box-footer">
                    Vedi scadenze <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- Widget scadenze imminenti --}}
    @if ($certExpiring30Count > 0 || $subExpiring7Count > 0)
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                        Scadenze imminenti
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('backoffice.members.expiry') }}" class="btn btn-warning btn-sm">
                            Vai al pannello <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <span class="badge badge-{{ $certExpiring30Count > 0 ? 'warning' : 'success' }} badge-pill mr-2" style="font-size:1.1rem;min-width:2rem;">
                                    {{ $certExpiring30Count }}
                                </span>
                                <span>Certificati medici in scadenza entro 30 giorni</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <span class="badge badge-{{ $subExpiring7Count > 0 ? 'danger' : 'success' }} badge-pill mr-2" style="font-size:1.1rem;min-width:2rem;">
                                    {{ $subExpiring7Count }}
                                </span>
                                <span>Abbonamenti in scadenza entro 7 giorni</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
