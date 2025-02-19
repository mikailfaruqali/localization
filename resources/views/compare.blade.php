@extends('snawbar-localization::layout')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="card-title">Edit</h5>
    </div>
    <div class="card-body">
        @foreach ($errors->all() as $error)
            <div class="alert alert-danger">{{ $error }}</div>
        @endforeach
        <form method="POST" action="{{ route('snawbar.localization.update') }}" id="localization-form">
            @csrf
            <table class="table table-striped table-bordered text-center">
                <thead>
                    <tr>
                        @foreach ($content->keys() as $language)
                            <th scope="col">{{ strtoupper($language) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($baseKeys as $value)
                        <tr>
                            @foreach ($content->keys() as $language)
                                <td>
                                    <textarea name="{{ $language }}[{{ $value }}]" class="form-control" rows="2">{{ $content[$language][$value] ?? '' }}</textarea>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <input type="hidden" name="languages" value="{{ json_encode($content->keys()) }}">
            <input type="hidden" name="baseKeys" value="{{ json_encode($baseKeys) }}">
            <input type="hidden" name="file" value="{{ $file }}">
        </form>
    </div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary" form="localization-form">Save Changes</button>
    </div>
</div>
@endsection
