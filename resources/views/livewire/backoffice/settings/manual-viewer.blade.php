<div x-data="{ search: '' }">
    <div class="row">

        {{-- Sidebar sezioni --}}
        <div class="col-md-3">
            <div class="card card-outline card-primary">
                <div class="card-header p-2">
                    <input type="text"
                           class="form-control form-control-sm"
                           placeholder="Cerca sezione..."
                           x-model="search"
                           aria-label="Filtra sezioni del manuale">
                </div>
                <div class="card-body p-0">
                    <ul class="nav flex-column" role="list">
                        @forelse ($sections as $slug => $section)
                            <li class="nav-item"
                                x-data="{ title: @js(strtolower($section['title'])) }"
                                x-show="search === '' || title.includes(search.toLowerCase())"
                                role="listitem">
                                <a href="#"
                                   class="nav-link py-2 px-3 {{ $currentSlug === $slug ? 'font-weight-bold' : 'text-dark' }}"
                                   style="{{ $currentSlug === $slug ? 'border-left: 3px solid #E85D04; background: #f4f6f9;' : 'border-left: 3px solid transparent;' }}"
                                   wire:click.prevent="selectSection('{{ $slug }}')"
                                   aria-current="{{ $currentSlug === $slug ? 'page' : 'false' }}">
                                    {{ $section['title'] }}
                                    @if (isset($flagStatuses[$slug]))
                                        <span class="badge badge-{{ $flagStatuses[$slug] ? 'success' : 'secondary' }} ml-1"
                                              style="font-size: 10px; vertical-align: middle;">
                                            {{ $flagStatuses[$slug] ? 'ON' : 'OFF' }}
                                        </span>
                                    @endif
                                </a>
                            </li>
                        @empty
                            <li class="nav-item px-3 py-2 text-muted small">Nessuna sezione disponibile.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Contenuto sezione --}}
        <div class="col-md-9">
            <div class="card">
                <div class="card-body manual-content">
                    @if ($renderedHtml !== '')
                        {!! $renderedHtml !!}
                    @else
                        <p class="text-muted">Seleziona una sezione dalla lista.</p>
                    @endif
                </div>
            </div>
        </div>

    </div>

    <style>
        .manual-content h1 { font-size: 1.6rem; font-weight: 700; margin-bottom: 1rem; padding-bottom: .5rem; border-bottom: 1px solid #dee2e6; }
        .manual-content h2 { font-size: 1.25rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: .6rem; }
        .manual-content h3 { font-size: 1.05rem; font-weight: 600; margin-top: 1.2rem; margin-bottom: .4rem; }
        .manual-content p  { margin-bottom: .75rem; line-height: 1.65; }
        .manual-content ul, .manual-content ol { padding-left: 1.4rem; margin-bottom: .75rem; }
        .manual-content li { margin-bottom: .25rem; line-height: 1.6; }
        .manual-content code { background: #f4f6f9; padding: 1px 5px; border-radius: 3px; font-size: .875em; }
        .manual-content pre  { background: #f4f6f9; padding: .75rem 1rem; border-radius: 4px; overflow-x: auto; }
        .manual-content pre code { background: none; padding: 0; }
        .manual-content strong { font-weight: 600; }
        .manual-content blockquote { border-left: 4px solid #dee2e6; padding-left: 1rem; color: #6c757d; margin: 1rem 0; }
    </style>
</div>
