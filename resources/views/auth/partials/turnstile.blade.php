{{-- Captcha Cloudflare Turnstile (dicek di server oleh App\Rules\Turnstile).
     Tidak tampil kalau TURNSTILE_SITE_KEY belum diisi. Pesan error-nya ikut
     tampil di blok error masing-masing halaman. --}}
@if (config('services.turnstile.site_key'))
    <div class="col-12">
        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-size="flexible"></div>
    </div>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
