@php($showPassword = config('google-auth.show_password_login'))

@unless($showPassword)
    <style>
        /* Oculta el formulario nativo de email/password. El botón de Google se inyecta
           ANTES del <form> (sibling), así que este selector no lo afecta. */
        form[action="{{ route('admin.session.store') }}"] {
            display: none !important;
        }
    </style>
@endunless

<div style="font-family: 'Poppins', sans-serif; padding: 16px 16px 0;">
    @if(session('warning'))
        <p style="color:#F37043; font-weight:600; margin-bottom:12px;">{{ session('warning') }}</p>
    @endif
    @if(session('error'))
        <p style="color:#F37043; font-weight:600; margin-bottom:12px;">{{ session('error') }}</p>
    @endif

    <a href="{{ route('google-auth.redirect') }}"
       style="display:flex; align-items:center; justify-content:center; gap:8px;
              background:#6950A1; color:#fff; font-weight:700; text-decoration:none;
              padding:12px 16px; border-radius:8px;">
        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="" width="18" height="18" style="background:#fff;border-radius:2px;padding:2px;">
        Entrar con Google
    </a>
</div>
