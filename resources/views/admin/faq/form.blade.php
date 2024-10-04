<div class="row mb-2">
    <div class="col-md-12">
        <div class="mb-3">
            <x-form-input name="question" value="{{ old('question', $faq->question ?? '') }}" label="Question" placeholder="Please enter question"/>
        </div>
    </div>

    <div class="col-md-12">
        <div class="mb-3">
            <label for="answer">Answer</label>
            <textarea id="answer" name="answer" class="form-control" placeholder="Please enter answer">{{ $faq->answer ?? '' }}</textarea>
            @if ($errors->has('answer'))
                <div class="text-danger">{{ $errors->first('answer') }}</div>
            @endif
        </div>
    </div>
</div>

<div class="row mb-2">
    <div class="col-lg-6 mb-2">
        <button type="submit" class="btn btn-primary w-md">Submit</button>
    </div>
</div>

<x-include-plugins :plugins="['contentEditor']"></x-include-plugins>