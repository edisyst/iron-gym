<div>
    @foreach ($groups as $groupName => $commands)
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">{{ $groupName }}</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:220px">Comando</th>
                            <th>Descrizione</th>
                            <th class="text-center" style="width:130px">Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($commands as $key => $cmd)
                            <tr>
                                <td class="align-middle">
                                    <i class="{{ $cmd['icon'] }} mr-1 text-muted"></i>
                                    <strong>{{ $cmd['label'] }}</strong>
                                </td>
                                <td class="align-middle">
                                    <small>{{ $cmd['description'] }}</small>
                                </td>
                                <td class="text-center align-middle">
                                    <button wire:click="runCommand('{{ $key }}')"
                                            wire:loading.attr="disabled"
                                            wire:target="runCommand('{{ $key }}')"
                                            class="btn btn-{{ $cmd['style'] }} btn-sm">
                                        <span wire:loading.remove wire:target="runCommand('{{ $key }}')">
                                            Esegui
                                        </span>
                                        <span wire:loading wire:target="runCommand('{{ $key }}')">
                                            <i class="fas fa-spinner fa-spin"></i>
                                        </span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    @if ($hasOutput)
        <div class="card border-secondary">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="fas fa-terminal mr-1"></i>
                    Output — <strong>{{ $lastLabel }}</strong>
                </span>
                <button wire:click="clearOutput" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times"></i> Chiudi
                </button>
            </div>
            <div class="card-body p-0">
                <pre class="mb-0 p-3" style="background:#1e1e1e;color:#d4d4d4;border-radius:0 0 .25rem .25rem;font-size:.8rem;max-height:400px;overflow-y:auto;white-space:pre-wrap;word-break:break-all">{{ $output }}</pre>
            </div>
        </div>
    @endif
</div>
