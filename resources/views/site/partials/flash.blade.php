@if (session('success') || session('error'))
  <div class="se-container mt-4">
    <div @class(['se-alert', 'se-alert--success' => session('success'), 'se-alert--error' => ! session('success')]) role="status">
      {{ session('success') ?? session('error') }}
    </div>
  </div>
@endif
