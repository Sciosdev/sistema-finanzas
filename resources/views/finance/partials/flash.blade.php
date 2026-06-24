@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        @if (session('undo_delete'))
            @php($undoDelete = session('undo_delete'))
            <form method="POST" action="{{ route('finance.security.undo-delete', $undoDelete['token']) }}" class="d-inline-block ms-2">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-success">
                    {{ $undoDelete['label'] ?? 'Deshacer' }}
                </button>
            </form>
            <small class="text-muted ms-1">Disponible por 2 minutos.</small>
        @endif
        @if (session('backup_download'))
            @php($backupDownload = session('backup_download'))
            <a
                href="{{ route('finance.security.backups.download', ['type' => $backupDownload['type'], 'filename' => $backupDownload['name']]) }}"
                class="btn btn-sm btn-outline-success ms-2"
            >
                Descargar ahora
            </a>
        @endif
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Revisa estos datos:</strong>
        <ul class="mb-0 mt-2">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
@endif
