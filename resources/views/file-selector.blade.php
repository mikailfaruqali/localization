@extends('snawbar-localization::layout')

@section('content')
<div class="content-wrapper">
    <div class="grid grid-cols-1 gap-4 mb-5">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-folder"></i> Select Translation File
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('snawbar.localization.compare') }}" class="needs-validation" novalidate id="file-selection-form">
                    <div class="grid grid-cols-1 gap-3">
                        <div>
                            <label class="label">Choose a translation file</label>
                            <div class="file-picker">
                                @foreach ($files as $item)
                                    <div class="file-card {{ request('file') == $item ? 'selected' : '' }} {{ isset($missingKeys[$item]) ? 'has-problems' : '' }}" data-value="{{ $item }}">
                                        <div class="file-info">
                                            @if(isset($missingKeys[$item]))
                                                <i class="fas fa-exclamation-triangle file-status-icon problem-icon"></i>
                                            @else
                                                <i class="fas fa-check-circle file-status-icon complete-icon"></i>
                                            @endif
                                            <div class="file-details">
                                                <span class="file-name">{{ $item }}</span>
                                                <span class="file-type">{{ $fileStatuses[$item] }}</span>
                                            </div>
                                        </div>
                                        <div class="file-actions">
                                            @if(request('file') == $item)
                                                <i class="fas fa-check-circle selected-check"></i>
                                            @else
                                                <i class="fas fa-circle-plus add-icon"></i>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                                <input type="hidden" name="file" id="selected-file" value="{{ request('file') }}" required>
                            </div>
                            @error('file')
                                <div class="text-danger mt-2">
                                    <i class="fas fa-exclamation-triangle"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
