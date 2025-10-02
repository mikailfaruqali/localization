@extends('snawbar-localization::layout')

@section('content')
    <div class="card card-shadow">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                Overrides
            </h5>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#add-override-modal">
                <i class="fas fa-plus me-2"></i> Add
            </button>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="overrides-table">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Key</th>
                            <th class="text-center">Language</th>
                            <th>Value</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($overrides as $override)
                            <tr data-key="{{ $override->key }}" data-locale="{{ $override->locale }}">
                                <td class="text-center align-middle">{{ $loop->iteration }}</td>
                                <td class="align-middle">
                                    <code class="text-primary">{{ $override->key }}</code>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="badge bg-primary">{{ strtoupper($override->locale) }}</span>
                                </td>
                                <td class="align-middle">
                                    <span class="text-truncate d-block" title="{{ $override->value }}">
                                        {{ $override->value }}
                                    </span>
                                </td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-outline-primary btn-sm edit-override-btn me-2"
                                        data-id="{{ $override->id }}" data-key="{{ $override->key }}"
                                        data-locale="{{ $override->locale }}" data-value="{{ $override->value }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm delete-override-btn"
                                        data-id="{{ $override->id }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="no-overrides-row">
                                <td colspan="5" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox text-muted mb-2 empty-icon"></i>
                                        <p class="text-muted mb-0">No overrides found</p>
                                        <p class="small text-muted">Click "Add" to create your first override</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Override Modal -->
    <div class="modal fade" id="add-override-modal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i> Add Overrides
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="override-key-search" class="form-label">Search Translation Key</label>
                        <select class="form-control" id="override-key-search"></select>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="modal-overrides-table">
                            <thead>
                                <tr>
                                    <th>Key</th>

                                    @foreach ($languages as $language)
                                        <th class="text-center">{{ strtoupper($language) }}</th>
                                    @endforeach
                                    
                                    <th class="text-center action-column">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="modal-overrides-tbody">
                                <tr id="modal-no-keys-row">
                                    <td colspan="{{ count($languages) + 2 }}" class="text-center text-muted py-4">
                                        Search and select keys above to add them here
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <input type="hidden" id="modal-languages-data" value="{{ json_encode(array_values($languages)) }}">
                    <input type="hidden" id="override-search-url" value="{{ route('snawbar.overrides.search') }}">
                    <input type="hidden" id="override-original-values-url" value="{{ route('snawbar.overrides.originalValues') }}">
                    <input type="hidden" id="override-store-url" value="{{ route('snawbar.overrides.store') }}">
                    <input type="hidden" id="override-update-url" value="{{ route('snawbar.overrides.update') }}">
                    <input type="hidden" id="override-delete-url" value="{{ route('snawbar.overrides.destroy') }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="save-override-btn">
                        <i class="fas fa-save me-2"></i> Save All
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Override Modal -->
    <div class="modal fade" id="edit-override-modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i> Edit Override
                    </h5>
                </div>
                <div class="modal-body">
                    <form id="edit-override-form">
                        <div class="mb-3">
                            <label for="edit-override-value" class="form-label">Value</label>
                            <textarea class="form-control" id="edit-override-value" name="value" rows="4"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="update-override-btn">
                        <i class="fas fa-save me-2"></i> Update
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
