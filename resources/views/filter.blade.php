@extends('snawbar-localization::layout')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Filter</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('snawbar.localization.compare') }}">
            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="languages" class="form-label">Select Languages</label>
                    <select class="form-select" id="languages" name="languages[]" multiple="multiple" data-placeholder="Select Languages">
                        @foreach ($languages as $item)
                            <option value="{{ $item }}">{{ $item }}</option>
                        @endforeach
                    </select>
                    @error('languages')
                        <div class="form-text">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-12 mt-3">
                    <label for="file" class="form-label">Choose a file</label>
                    <select class="form-select" id="file" name="file">
                        @foreach ($files as $item)
                            <option value="{{ $item }}">{{ $item }}</option>
                        @endforeach
                    </select>
                    @error('file')
                        <div class="form-text">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="text-center mt-3">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>
</div>
@endsection
