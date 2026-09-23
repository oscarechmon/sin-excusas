@php($socials = \App\Support\SiteSettings::socialLinks())

@if ($socials)
  <ul class="se-social" aria-label="Redes sociales">
    @foreach ($socials as $social)
      <li>
        <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="{{ $social['label'] }}">
          @switch($social['key'])
            @case('instagram')
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
              </svg>
              @break
            @case('facebook')
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v7h3v-7h2.5l.5-3H13v-2c0-.6.4-1 1-1Z"/>
              </svg>
              @break
            @case('tiktok')
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M16.5 3c.4 2 1.6 3.4 3.5 3.7v2.6c-1.3.1-2.5-.3-3.6-1v5.9c0 3.4-2.6 5.8-5.8 5.8S5 17.6 5 14.4c0-3 2.4-5.4 5.6-5.3v2.7c-.3-.1-.6-.1-.9-.1-1.6 0-2.9 1.3-2.9 2.9s1.3 2.9 2.9 2.9 3-1.2 3-2.9V3h2.8Z"/>
              </svg>
              @break
            @case('youtube')
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M21.6 7.2c-.2-.9-.9-1.6-1.8-1.8C18.2 5 12 5 12 5s-6.2 0-7.8.4c-.9.2-1.6.9-1.8 1.8C2 8.8 2 12 2 12s0 3.2.4 4.8c.2.9.9 1.6 1.8 1.8C5.8 19 12 19 12 19s6.2 0 7.8-.4c.9-.2 1.6-.9 1.8-1.8.4-1.6.4-4.8.4-4.8s0-3.2-.4-4.8ZM10 15V9l5.2 3L10 15Z"/>
              </svg>
              @break
          @endswitch
        </a>
      </li>
    @endforeach
  </ul>
@endif
