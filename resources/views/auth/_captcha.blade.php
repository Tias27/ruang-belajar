<div class="captcha-field">
    <div><label for="{{ $kind }}-captcha">Hitung sebentar</label><p>Verifikasi sebelum lanjut.</p></div>
    <div class="captcha-controls">
        <span id="{{ $kind }}-captcha-question" aria-live="polite">{{ $captcha['num1'] }} {{ $captcha['operator'] }} {{ $captcha['num2'] }} =</span>
        <input id="{{ $kind }}-captcha" name="math_captcha" inputmode="numeric" pattern="[0-9]+" autocomplete="off" aria-describedby="{{ $kind }}-captcha-question {{ $kind }}-captcha-error" aria-invalid="{{ $errors->has('math_captcha') ? 'true' : 'false' }}" placeholder="?" required>
        <button type="button" data-captcha-refresh="{{ $kind }}" data-captcha-url="{{ route('captcha.refresh') }}" aria-label="Ganti hitungan"><span data-icon="rotate"></span></button>
    </div>
</div>
<span class="field-error captcha-error" id="{{ $kind }}-captcha-error" aria-live="polite" @if (!$errors->has('math_captcha')) hidden @endif>@error('math_captcha'){{ $message }}@enderror</span>
