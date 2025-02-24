@extends('snawbar-localization::layout')

@section('content')
<div class="card shadow mb-100">
    <div class="card-header">
        <h5 class="card-title">Edit</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('snawbar.localization.update') }}" id="localization-form">
            @csrf
            <table class="table table-striped table-bordered text-center" id="translation-table">
                <thead>
                    <tr>
                        <td class="col-2">KEY</td>
                        @foreach ($content->keys() as $language)
                            <th scope="col">{{ strtoupper($language) }}</th>
                        @endforeach
                        <td class="col-1">Action</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($baseKeys as $value)
                        <tr>
                            <td>{{ $value }}</td>
                            @foreach ($content->keys() as $language)
                                <td>
                                    <textarea name="{{ $language }}[{{ $value }}]" class="form-control" rows="2">{{ $content[$language][$value] ?? '' }}</textarea>
                                </td>
                            @endforeach
                            <td>
                                <a href="javascript:void(0)" class="btn btn-danger" onclick="deleteRow(this)">Delete</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <input type="hidden" name="languages" value="{{ json_encode($content->keys()) }}">
            <input type="hidden" name="file" value="{{ $file }}">
        </form>
    </div>
</div>
<div class="fixed-bottom text-center bg-white shadow-lg p-2">
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#new-row-modal">Add New Row</button>
    <button type="button" class="btn btn-primary" data-form="localization-form" onclick="saveChanges(this)">Save Changes</button>
</div>
<div class="modal" id="new-row-modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Row</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group key-form-group">
                    <label for="key" class="col-form-label">Key</label>
                    <textarea class="form-control" name="key" id="key" rows="2"></textarea>
                </div>
                <input type="hidden" name="languages" value="{{ json_encode($content->keys()) }}">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="addNewRow(this)">Add</button>
            </div>
        </div>
    </div>
</div>
@endsection
