{{--
    Pesan flash dari controller: ->with('success', '...') / 'error' / 'warning' / 'info'.
    Dipasang otomatis di layouts/app.blade.php.
--}}
@php
    $flashTypes = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
@endphp

@foreach ($flashTypes as $key => $alertClass)
    @if (session($key))
        <div class="alert alert-{{ $alertClass }} alert-dismissible text-white fade show" role="alert">
            <span class="text-sm">{{ session($key) }}</span>
            <button type="button" class="btn-close text-lg py-3 opacity-10" data-bs-dismiss="alert" aria-label="Tutup">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
@endforeach
