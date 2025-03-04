@extends('snawbar-localization::layout')

@section('content')
<div class="card shadow">
    <div class="card-header">
        <h5 class="card-title">Filter</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('snawbar.localization.compare') }}">
            <div class="row mb-3">
                <div class="col-md-12">
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

@foreach ($missingKeys as $file => $languages)
    <div class="card shadow mt-3">
        <div class="card-header">
            <h5 class="card-title">Untranslated Keys in {{ $file }}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach ($languages as $language => $keys)
                    <div class="col-12 col-md-4 mb-3">
                        <div class="card">
                            <div class="card-header text-center">
                                <strong>{{ strtoupper($language) }}</strong>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    @foreach ($keys as $key => $translation)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>{{ $key }}</span>
                                            <a href="{{ route('snawbar.localization.compare', ['file' => $file]) }}#{{ $key }}" class="btn btn-primary btn-sm" target="_blank">Find</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endforeach
@endsection
