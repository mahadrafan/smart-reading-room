{{-- no. telepon peminjam ($user): diklik langsung membuka chat WhatsApp di tab baru.
     nomor yang bukan format nomor HP Indonesia ditampilkan sebagai teks biasa --}}
@if ($user->whatsapp_url)
    <a href="{{ $user->whatsapp_url }}" target="_blank" rel="noopener" class="tautan-wa"
       title="Chat WhatsApp {{ $user->name }}">
        <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false">
            <path fill="currentColor" d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.6-1.2A9 9 0 1 0 12 3Zm0 16.2c-1.4 0-2.8-.4-4-1.1l-.3-.2-2.7.7.7-2.6-.2-.3A7.2 7.2 0 1 1 12 19.2Zm4-5.4c-.2-.1-1.3-.6-1.5-.7-.2-.1-.4-.1-.5.1l-.7.9c-.1.2-.3.2-.5.1a5.9 5.9 0 0 1-2.9-2.6c-.2-.4.2-.4.6-1.2.1-.1 0-.3 0-.4l-.7-1.6c-.2-.4-.4-.4-.5-.4h-.4c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 1.9s.8 2.2.9 2.3c.1.2 1.6 2.5 3.9 3.5 1.5.6 2 .7 2.7.6.4-.1 1.3-.5 1.5-1.1.2-.5.2-1 .1-1.1l-.6-.4Z"/>
        </svg><span>{{ $user->telepon_tampil }}</span>
    </a>
@else
    <span title="{{ $user->phone ? 'Nomor tidak dikenali sebagai nomor WhatsApp' : '' }}">{{ $user->telepon_tampil ?: '-' }}</span>
@endif
