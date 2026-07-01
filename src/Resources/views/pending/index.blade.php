<x-admin::layouts>
    <x-slot:title>Aprobaciones pendientes</x-slot:title>

    <div style="font-family:'Poppins',sans-serif; padding:16px;">
        <h1 style="font-weight:700;">Usuarios pendientes de aprobación</h1>

        @if(session('success'))
            <p style="color:#00B26B; font-weight:600;">{{ session('success') }}</p>
        @endif

        <table style="width:100%; margin-top:12px;">
            <thead><tr><th>Nombre</th><th>Correo</th><th></th></tr></thead>
            <tbody>
            @forelse($pending as $user)
                <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <form method="POST" action="{{ route('google-auth.pending.approve', $user->id) }}">
                            @csrf
                            <button type="submit" style="background:#00B26B; color:#fff; font-weight:700; border:0; padding:8px 12px; border-radius:6px;">
                                Aprobar
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="3">No hay usuarios pendientes.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-admin::layouts>
