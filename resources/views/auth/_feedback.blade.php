@if (session('status'))
    <p class="guest-notice" role="status">{{ session('status') }}</p>
@endif
@if ($errors->any())
    <div class="guest-notice guest-notice-error" role="alert">
        <p>Ada yang perlu dicek dulu.</p>
        <ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
    </div>
@endif
