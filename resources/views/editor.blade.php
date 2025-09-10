@extends('snawbar-localization::layout')

@section('content')
    <!-- Filter Controls -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-shadow p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="filter-buttons">
                        <button type="button" class="btn btn-outline-warning btn-sm" id="show-missing-btn">
                            <i class="fas fa-filter me-2"></i>Show Missing Only
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="show-all-btn">
                            <i class="fas fa-list me-2"></i>Show All
                        </button>
                    </div>
                    <div class="filter-status">
                        <small class="text-muted" id="filter-status">Showing all keys</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Translation Table -->
    <div class="card card-shadow table-responsive">
        <div class="table-responsive">
            <form method="POST" action="{{ route('snawbar.localization.update') }}" id="localization-form">
                @csrf
                <table class="table table-hover mb-0" id="translation-table">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Key</th>

                            @foreach ($content->keys() as $language)
                                <th class="text-center">{{ strtoupper($language) }}</th>
                            @endforeach

                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($baseKeys as $key)
                            <tr id="{{ $key }}" class="translation-row">
                                <td class="text-center align-middle">{{ $loop->iteration }}</td>
                                <td class="align-middle">{{ $key }}</td>

                                @foreach ($content->keys() as $language)
                                    <td class="translation-cell">
                                        <div class="position-relative">
                                            <textarea name="{{ $language }}[{{ $key }}]"
                                                class="form-control translation-textarea {{ when(! isset($content[$language][$key]), 'bg-danger text-white') }}" rows="3"
                                                placeholder="Enter {{ strtoupper($language) }} translation..." data-language="{{ $language }}"
                                                data-key="{{ $key }}">{{ Arr::get($content, sprintf('%s.%s', $language, $key), '') }}</textarea>
                                        </div>
                                    </td>
                                @endforeach

                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-outline-danger btn-sm delete-btn"
                                        data-key="{{ $key }}" title="Delete this translation key">
                                        <i class="fas fa-times"></i>
                                    </button>
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

    <!-- Fixed Action Buttons -->
    <div class="fixed-bottom bg-white py-2 px-1 card-shadow border-top">
        <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#new-key-modal">
                <i class="fas fa-plus me-2"></i> Add New
            </button>
            <button type="button" class="btn btn-primary save-changes-btn">
                <i class="fas fa-save me-2"></i> Save
            </button>
            <a href="{{ route('snawbar.localization.view') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>


    <!-- Add New Key Modal -->
    <div class="modal fade" id="new-key-modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i> Add New Translation Key
                    </h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <textarea class="form-control" name="key" id="key" rows="3"
                            placeholder="Enter translation key (e.g., welcome.message)" required></textarea>
                    </div>
                    <input type="hidden" name="languages" value="{{ json_encode($content->keys()) }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary add-new-row-btn">
                        <i class="fas fa-plus me-2"></i> Add Key
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
