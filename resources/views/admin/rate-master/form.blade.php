<div class="row mb-2">
    <div class="col-md-12">
        <div class="mb-3">
            <x-form-input name="level" value="{{ old('level', $rateMaster->level ?? '') }}" label="Level" placeholder="Enter your Level"  required="true" />
        </div>
    </div>

    <div class="col-md-12">
        <div class="mb-3">
            <x-form-input name="rate" value="{{ old('rate', $rateMaster->rate ?? '' ) }}" label="Rate" placeholder="Enter your Rate" required="true" type="number" step="any"/>
        </div>
    </div>
</div>

<div class="row mb-2">
    <div class="col-lg-6 mb-2">
        <button type="submit" class="btn btn-primary w-md">Submit</button>
    </div>
</div>