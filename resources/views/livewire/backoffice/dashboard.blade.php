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
                <a href="{{ route('backoffice.subscriptions.index') }}" class="small-box-footer">
                    Vedi tutti <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
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
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $medicalCertIssuesCount }}</h3>
                    <p>Certificati scaduti / in scadenza</p>
                </div>
                <div class="icon"><i class="fas fa-file-medical"></i></div>
                <a href="{{ route('backoffice.members.index') }}?filter=cert_issues" class="small-box-footer">
                    Vedi tesserati <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>
