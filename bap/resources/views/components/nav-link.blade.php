@props(['href', 'icon', 'label', 'active' => null])

@php($isActive = is_null($active) ? request()->url() === $href : (bool) $active)

<a href="{{ $href }}" class="group flex items-center gap-3 rounded-lg px-3 py-2.5 transition {{ $isActive ? 'bg-campus-50 text-campus-900 ring-1 ring-campus-100' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
    <i data-lucide="{{ $icon }}" class="h-4 w-4 {{ $isActive ? 'text-campus-700' : 'text-slate-400 group-hover:text-campus-700' }}"></i>
    <span>{{ $label }}</span>
</a>
