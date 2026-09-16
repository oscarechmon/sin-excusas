@php
  $type ??= 'text';
  $id = 'f-'.$name;
@endphp

<div class="se-field {{ $class ?? '' }}">
  <label for="{{ $id }}">{{ $label }}@if (! empty($required)) <span aria-hidden="true">*</span>@endif</label>

  @if ($type === 'textarea')
    <textarea id="{{ $id }}" name="{{ $name }}" rows="3"
              @class(['form-control', 'is-invalid' => $errors->has($name)])>{{ old($name, $value ?? '') }}</textarea>
  @else
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
           value="{{ $type === 'password' ? '' : old($name, $value ?? '') }}"
           @class(['form-control', 'is-invalid' => $errors->has($name)])
           @if (! empty($required)) required @endif
           @if (! empty($deliveryOnly)) data-delivery-required @endif
           @if (! empty($autocomplete)) autocomplete="{{ $autocomplete }}" @endif>
  @endif

  @error($name)
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>
