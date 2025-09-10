@extends('snawbar-localization::layout')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-10 col-11">
            <!-- Header Section -->
            <div class="text-center mb-4 px-3 px-sm-0">
                <div
                    class="d-inline-flex align-items-center justify-content-center bg-primary rounded-circle mb-3 file-selector-header">
                    <i class="fas fa-language text-white fs-1"></i>
                </div>
                <h2 class="fw-bold text-dark mb-2">Choose Translation File</h2>
                <p class="text-muted fs-5">Select a language file to start editing translations</p>
            </div>

            <!-- File Cards -->
            <form method="GET" action="{{ route('snawbar.localization.compare') }}" id="file-selection-form">
                <div class="row g-3 px-2 px-sm-0">
                    @foreach ($files as $item)
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="card file-card h-100 {{ when(isset($missingKeys[$item]), 'border-warning', 'border-success') }}"
                                data-value="{{ $item }}">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="fas {{ when(isset($missingKeys[$item]), 'fa-exclamation-triangle text-warning', 'fa-check-circle text-success') }} fs-5"></i>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <h6 class="card-title mb-1 text-truncate">{{ $item }}</h6>
                                            <small class="text-muted">{{ $fileStatuses[$item] }}</small>
                                        </div>
                                        <div class="ms-2">
                                            <i class="fas fa-arrow-right text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <input type="hidden" name="file" id="selected-file" required>

                @error('file')
                    <div class="alert alert-danger mt-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>{{ $message }}
                    </div>
                @enderror
            </form>
        </div>
    </div>
@endsection
