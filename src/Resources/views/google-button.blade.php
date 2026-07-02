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
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="18" height="18" style="background:#fff;border-radius:2px;padding:2px;flex-shrink:0;" aria-hidden="true">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.36-8.16 2.36-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            <path fill="none" d="M0 0h48v48H0z"/>
        </svg>
        Entrar con Google
    </a>
</div>
