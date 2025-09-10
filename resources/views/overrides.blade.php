@extends('snawbar-localization::layout')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h3 class="card-title mb-1">
                        <i class="fas fa-edit me-2"></i>Translation Overrides
                    </h3>
                    <p class="card-text mb-0">Create and manage custom translation overrides that take precedence over file-based translations</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Controls -->
    <div class="row mb-4">
        <div class="col-md-6">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#add-override-modal">
                <i class="fas fa-plus me-1"></i>Add Translation Override
            </button>
        </div>
        <div class="col-md-6 text-end">
            <span class="badge bg-light text-dark border fs-6">
                <i class="fas fa-database me-1"></i>
                Total overrides: <strong id="total-count">{{ count($overrides) }}</strong>
            </span>
        </div>
    </div>
    
    <!-- Search -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" class="form-control" id="search-input" placeholder="Search translation keys and values...">
                <button class="btn btn-outline-secondary" type="button" id="clear-search">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="overrides-table">
                        <thead class="table-light">
                            <tr>
                                <th class="col-number">#</th>
                                <th class="col-key">Translation Key</th>
                                @foreach ($languages as $language)
                                    <th class="col-language text-center">
                                        <span class="language-badge">{{ strtoupper($language) }}</span>
                                    </th>
                                @endforeach
                                <th class="col-actions text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($overrides as $key => $translations)
                                <tr data-key="{{ $key }}">
                                    <td class="text-muted">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="key-cell">
                                            <code class="key-text">{{ $key }}</code>
                                        </div>
                                    </td>
                                    @foreach ($languages as $language)
                                        <td class="text-center">
                                            @if(isset($translations[$language]))
                                                <span class="translation-value" title="{{ $translations[$language] }}">
                                                    {{ Str::limit($translations[$language], 50) }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-sm btn-outline-primary edit-override-btn" 
                                                    data-key="{{ $key }}" data-bs-toggle="modal" data-bs-target="#edit-override-modal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-override-btn" 
                                                    data-key="{{ $key }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr id="no-overrides-row">
                                    <td colspan="{{ count($languages) + 3 }}" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox text-muted mb-2" style="font-size: 2rem;"></i>
                                            <p class="text-muted mb-0">No translation overrides found</p>
                                            <p class="small text-muted">Click "Add Translation Override" to create your first override</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Override Modal -->
<div class="modal fade" id="add-override-modal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Add Translation Override
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="add-override-form">
                    <div class="mb-3">
                        <label for="override-key" class="form-label">Translation Key <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="override-key" name="key" placeholder="e.g., welcome.message" required>
                        <div class="form-text">Use dot notation for nested keys (e.g., auth.login.title)</div>
                    </div>
                    
                    <div class="translations-container">
                        <label class="form-label">Translations</label>
                        <div class="row">
                            @foreach ($languages as $language)
                                <div class="col-md-6 mb-3">
                                    <label for="translation-{{ $language }}" class="form-label">
                                        <span class="language-badge">{{ strtoupper($language) }}</span>
                                    </label>
                                    <textarea class="form-control" id="translation-{{ $language }}" 
                                              name="translations[{{ $language }}]" rows="3" 
                                              placeholder="Enter {{ $language }} translation"></textarea>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-override-btn">
                    <i class="fas fa-save me-1"></i>Save Override
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Override Modal -->
<div class="modal fade" id="edit-override-modal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Translation Override
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="edit-override-form">
                    <div class="mb-3">
                        <label for="edit-override-key" class="form-label">Translation Key</label>
                        <input type="text" class="form-control" id="edit-override-key" name="key" readonly>
                    </div>
                    
                    <div class="translations-container">
                        <label class="form-label">Translations</label>
                        <div class="row">
                            @foreach ($languages as $language)
                                <div class="col-md-6 mb-3">
                                    <label for="edit-translation-{{ $language }}" class="form-label">
                                        <span class="language-badge">{{ strtoupper($language) }}</span>
                                    </label>
                                    <textarea class="form-control" id="edit-translation-{{ $language }}" 
                                              name="translations[{{ $language }}]" rows="3" 
                                              placeholder="Enter {{ $language }} translation"></textarea>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="update-override-btn">
                    <i class="fas fa-save me-1"></i>Update Override
                </button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection
