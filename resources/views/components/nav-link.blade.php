@props(['href', 'icon', 'label', 'active' => null])

@php($isActive = is_null($active) ? request()->url() === $href : (bool) $active)

<a href="{{ $href }}" @class(['active' => $isActive]) @if($isActive) aria-current="page" @endif>
    <span data-icon="{{ $icon }}"></span>
    <span>{{ $label }}</span>
</a>
