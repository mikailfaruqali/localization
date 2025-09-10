@extends('snawbar-localization::layout')

@section('content')
<div class="editor-wrapper">
    <div class="container-fluid">
        <div class="editor-header">
            <div class="header-content">
                <div class="header-info">
                    <h3 class="editor-title">{{ $file }}</h3>
                </div>
                <div class="header-stats">
                    <div class="stat-card stat-total">
                        <div class="stat-value">{{ $totalKeys }}</div>
                        <div class="stat-label">Total Keys</div>
                    </div>
                    <div class="stat-card stat-complete">
                        <div class="stat-value">{{ $completedCount }}</div>
                        <div class="stat-label">Complete</div>
                    </div>
                    <div class="stat-card stat-missing">
                        <div class="stat-value">{{ $missingCount }}</div>
                        <div class="stat-label">Missing</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="filter-controls mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="filter-buttons">
                    <button type="button" class="btn btn-outline-warning btn-sm" id="show-missing-btn">
                        <i class="fas fa-filter"></i> Show Missing Only
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="show-all-btn">
                        <i class="fas fa-list"></i> Show All
                    </button>
                </div>
                <div class="filter-info">
                    <small class="text-muted" id="filter-status">Showing all {{ $totalKeys }} keys</small>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <form method="POST" action="{{ route('snawbar.localization.update') }}" id="localization-form">
                        @csrf
                        <table class="table table-hover mb-0" id="translation-table">
                            <thead class="table-light">
                                <tr>
                                    <th class="col-number">#</th>
                                    <th class="col-key">Translation Key</th>
                                    @foreach ($content->keys() as $language)
                                        <th class="col-language text-center">{{ strtoupper($language) }}</th>
                                    @endforeach
                                    <th class="col-actions text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($baseKeys as $key)
                                    <tr id="{{ $key }}" class="translation-row">
                                        <td class="text-center align-middle">{{ $loop->iteration }}</td>
                                        <td class="align-middle">
                                            <div class="key-container">
                                                <code class="translation-key">{{ $key }}</code>
                                                @if (!isset($content->first()[$key]))
                                                    <div class="mt-1">
                                                        <span class="badge bg-danger">Missing in base</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        @foreach ($content->keys() as $language)
                                            @php
                                                $hasTranslation = isset($content[$language][$key]);
                                                $translationValue = $content[$language][$key] ?? '';
                                            @endphp
                                            <td class="translation-cell">
                                                <div class="position-relative">
                                                    <textarea name="{{ $language }}[{{ $key }}]"
                                                        class="translation-textarea {{ !$hasTranslation ? 'missing-field' : '' }}" rows="3"
                                                        placeholder="Enter {{ strtoupper($language) }} translation..." data-language="{{ $language }}"
                                                        data-key="{{ $key }}">{{ $translationValue }}</textarea>
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
        </div>
    </div>

    <div class="editor-footer">
        <div class="footer-content">
            <div class="footer-actions d-flex justify-content-center">
                <button type="button" class="btn btn-success" data-bs-toggle="modal"
                    data-bs-target="#new-key-modal">
                    <i class="fas fa-plus"></i> Add New Key
                </button>
                <button type="button" class="btn btn-primary save-changes-btn">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <a href="{{ route('snawbar.localization.view') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Files
                </a>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="new-key-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Translation Key</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea class="form-control key-input" name="key" id="key" rows="3"
                    placeholder="Enter translation key..." required></textarea>
                <input type="hidden" name="languages" value="{{ json_encode($content->keys()) }}">
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary add-new-row-btn">Add Key</button>
            </div>
        </div>
    </div>
</div>
@endsection
