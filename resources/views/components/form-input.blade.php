<div class="form-group">
    <label for="{{ $name }}">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
    @if($type!=='password')
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ old($name, $value) }}"
            {{ $attributes->merge(['class' => 'form-control' . ($errors->has($name) ? ' is-invalid' : '')]) }}
        >
    @else
    <div class="input-group auth-pass-inputgroup">
        <input
            type="password" name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $value) }}"
            aria-label="Password" aria-describedby="password-addon"
            {{ $attributes->merge(['class' => 'form-control' . ($errors->has($name) ? ' is-invalid' : '')]) }}
        >
        <button class="btn btn-light" type="button" id="password-addon">
            <i class="mdi mdi-eye-outline"></i>
        </button><br>

    @endif

    @if ($errors->has($name))
        <span class="invalid-feedback">
            {{ $errors->first($name) }}
        </span>
    @endif
</div>
