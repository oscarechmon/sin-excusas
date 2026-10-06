@php
  $type ??= 'text';
  $id = 'f-'.$name;
  $hintId = ! empty($hint) ? $id.'-hint' : null;
@endphp

<div class="se-field {{ $class ?? '' }}">
  <label for="{{ $id }}">{{ $label }}@if (! empty($required) || ! empty($groupRequired)) <span aria-hidden="true">*</span>@endif</label>

  @if ($type === 'textarea')
    <textarea id="{{ $id }}" name="{{ $name }}" rows="3"
              @if (! empty($placeholder)) placeholder="{{ $placeholder }}" @endif
              @class(['form-control', 'is-invalid' => $errors->has($name)])>{{ old($name, $value ?? '') }}</textarea>
  @elseif ($type === 'select')
    @php($selected = old($name, $value ?? ''))
    <select id="{{ $id }}" name="{{ $name }}"
            @class(['form-select', 'is-invalid' => $errors->has($name)])
            @if (! empty($required)) required @endif
            {{-- Obligatorio solo cuando su grupo está a la vista (main.js). --}}
            @if (! empty($groupRequired)) data-group-required @endif
            @if ($hintId) aria-describedby="{{ $hintId }}" @endif>
      @if (! empty($placeholder))<option value="">{{ $placeholder }}</option>@endif
      @foreach ($options as $optionValue => $optionLabel)
        <option value="{{ $optionValue }}" @selected((string) $selected === (string) $optionValue)>{{ $optionLabel }}</option>
      @endforeach
    </select>
  @else
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
           value="{{ $type === 'password' ? '' : old($name, $value ?? '') }}"
           @class(['form-control', 'is-invalid' => $errors->has($name)])
           @if (! empty($required)) required @endif
           @if (! empty($groupRequired)) data-group-required @endif
           @if (! empty($placeholder)) placeholder="{{ $placeholder }}" @endif
           @if (! empty($inputmode)) inputmode="{{ $inputmode }}" @endif
           @if (! empty($maxlength)) maxlength="{{ $maxlength }}" @endif
           @if (! empty($autocomplete)) autocomplete="{{ $autocomplete }}" @endif
           @if ($hintId) aria-describedby="{{ $hintId }}" @endif>
  @endif

  @if ($hintId)
    <p id="{{ $hintId }}" class="se-field__hint">{{ $hint }}</p>
  @endif

  @error($name)
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>
