<label class="crancy__item-label">{{ __('translate.Addon') }} * </label>
<select class="form-select crancy__item-input select2" name="addon_items[]" multiple>
    <option value="" disabled>{{ __('translate.Select Addon') }}</option>
    @foreach ($addons as $addon)
        <option {{ $addon->id == old('addon') ? 'selected' : '' }} value="{{ $addon->id }}">{{ $addon->translate->name }}</option>
    @endforeach
</select>
<script>
    $(document).ready(function () {
        "use strict";
        $('.select2').select2();
    });
</script>

