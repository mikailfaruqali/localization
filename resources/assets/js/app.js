class BaseAPI {
    constructor() {
        this.setupAjax();
    }

    setupAjax() {
        $.ajaxSetup({
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': this.getCsrfToken(),
                'Accept': 'application/json'
            }
        });
    }

    getCsrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || '';
    }

    handleError(error, defaultMessage = 'An error occurred') {
        let message = defaultMessage;
        let validationErrors = null;

        if (error.responseJSON) {
            message = error.responseJSON.message || message;
            validationErrors = error.responseJSON.errors;
        } else if (error.responseText) {
            try {
                const response = JSON.parse(error.responseText);
                message = response.message || message;
                validationErrors = response.errors;
            } catch (e) {
                message = error.statusText || message;
            }
        }

        if (validationErrors) {
            this.showValidationErrors(validationErrors);
        } else {
            this.showToast('error', message);
        }

        console.error('API Error:', error);
    }

    showValidationErrors(errors) {
        let errorMessages = [];

        if (typeof errors === 'object') {
            $.each(errors, function (field, messages) {
                if (Array.isArray(messages)) {
                    errorMessages = errorMessages.concat(messages);
                } else {
                    errorMessages.push(messages);
                }
            });
        }

        const errorHtml = errorMessages.join('<br>');
        this.showToast('error', errorHtml);
    }

    showToast(icon, message, timer = 3000) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: timer,
            timerProgressBar: true,
            didOpen: (toast) => {
                $(toast).on('mouseenter', () => Swal.stopTimer());
                $(toast).on('mouseleave', () => Swal.resumeTimer());
            }
        });

        return Toast.fire({
            icon: icon,
            html: message
        });
    }

    showSuccess(message = 'Operation completed successfully', timer = 2000) {
        return this.showToast('success', message, timer);
    }
}

class LocalizationManager extends BaseAPI {
    constructor() {
        super();
        this.init();
    }

    init() {
        this.setupButtonHandlers();
        this.initializeDeleteButtons();
        this.initializeAddKeyModal();
        this.initializeMissingKeyFilter();
        this.initializeFileSelector();
    }

    setupButtonHandlers() {
        $(document).on('click', '.save-changes-btn', (e) => {
            this.saveChanges($(e.currentTarget));
        });
    }

    saveChanges($button) {
        const $form = $('#localization-form');
        if (!$form.length) {
            return;
        }

        const originalHtml = $button.html();
        $button.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

        const formData = new FormData($form[0]);

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                this.showSuccess('Changes saved successfully');
            },
            error: (error) => {
                this.handleError(error, 'Failed to save changes');
            },
            complete: () => {
                $button.html(originalHtml).prop('disabled', false);
            }
        });
    }

    initializeDeleteButtons() {
        $(document).on('click', '.delete-btn', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const $button = $(e.currentTarget);
            const key = $button.data('key');
            this.confirmDelete(key);
            return false;
        });
    }

    initializeAddKeyModal() {
        const $addNewRowBtn = $('.add-new-row-btn');
        const $modal = $('#new-key-modal');
        const $keyInput = $('#key');

        if ($addNewRowBtn.length) {
            $addNewRowBtn.on('click', () => {
                const keyValue = $keyInput.val().trim();

                if (keyValue) {
                    this.addNewRowToTable(keyValue);

                    const modalInstance = bootstrap.Modal.getInstance($modal[0]) || new bootstrap.Modal($modal[0]);
                    modalInstance.hide();

                    $keyInput.val('');

                    setTimeout(() => {
                        const $newRow = $('#' + keyValue);

                        if ($newRow.length) {
                            $('html, body').animate({
                                scrollTop: $newRow.offset().top - ($(window).height() / 2)
                            }, 300);
                        }
                    }, 300);
                } else {
                    this.showToast('error', 'Please enter a translation key.');
                }
            });
        }
    }

    confirmDelete(key) {
        Swal.fire({
            title: 'Delete Translation Key?',
            text: `Are you sure you want to delete "${key}"? This cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                const $row = $('#' + key);
                if ($row.length) {
                    $row.css({
                        'transition': 'opacity 0.3s ease, transform 0.3s ease',
                        'opacity': '0',
                        'transform': 'translateX(-20px)'
                    });

                    setTimeout(() => {
                        $row.remove();
                    }, 200);
                }
            }
        });
    }

    addNewRowToTable(key) {
        const $tableBody = $('#translation-table tbody');
        const $languagesInput = $('input[name="languages"]');

        if (!$tableBody.length || !$languagesInput.length) {
            return;
        }

        const languages = JSON.parse($languagesInput.val());
        const rowCount = $tableBody.children().length + 1;

        let newRow = `
            <tr id="${key}" class="translation-row">
                <td class="text-center align-middle">${rowCount}</td>
                <td class="align-middle">
                    <div class="key-container">
                        <code class="translation-key">${key}</code>
                    </div>
                </td>`;

        $.each(languages, function (index, language) {
            newRow += `
                <td class="translation-cell">
                    <div class="position-relative">
                        <textarea name="${language}[${key}]"
                            class="translation-textarea missing-field form-control" rows="3"
                            placeholder="Enter ${language.toUpperCase()} translation..."
                            data-language="${language}"
                            data-key="${key}"></textarea>
                        <div class="status-indicator missing" title="Missing translation"></div>
                    </div>
                </td>`;
        });

        newRow += `
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-outline-danger btn-sm delete-btn"
                        data-key="${key}" title="Delete this translation key">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>`;

        $tableBody.append(newRow);
    }

    initializeMissingKeyFilter() {
        const $showMissingBtn = $('#show-missing-btn');
        const $showAllBtn = $('#show-all-btn');
        const $filterStatus = $('#filter-status');

        if (!$showMissingBtn.length || !$showAllBtn.length) {
            return;
        }

        const totalRows = $('#translation-table tbody tr').length;
        if ($filterStatus.length) {
            $filterStatus.text(`Showing all ${totalRows} keys`);
        }

        $showMissingBtn.on('click', () => {
            this.showMissingKeysOnly();
            $showMissingBtn.addClass('d-none');
            $showAllBtn.removeClass('d-none');
        });

        $showAllBtn.on('click', () => {
            this.showAllKeys();
            $showAllBtn.addClass('d-none');
            $showMissingBtn.removeClass('d-none');
        });
    }

    showMissingKeysOnly() {
        const $rows = $('#translation-table tbody tr');
        const $filterStatus = $('#filter-status');
        let visibleCount = 0;

        $rows.each(function (index) {
            const $row = $(this);
            const hasMissingFields = $row.find('.bg-danger, .missing-field').length > 0;

            if (hasMissingFields) {
                $row.show();
                visibleCount++;
                $row.find('.text-center').first().text(visibleCount);
            } else {
                $row.hide();
            }
        });

        if ($filterStatus.length) {
            $filterStatus.text(`Showing ${visibleCount} missing translation${visibleCount !== 1 ? 's' : ''}`);
        }

        if (visibleCount === 0) {
            this.showNoMissingMessage();
        }
    }

    showAllKeys() {
        const $rows = $('#translation-table tbody tr');
        const $filterStatus = $('#filter-status');

        $rows.each(function (index) {
            $(this).show();
            $(this).find('.text-center').first().text(index + 1);
        });

        if ($filterStatus.length) {
            $filterStatus.text(`Showing all ${$rows.length} keys`);
        }

        this.hideNoMissingMessage();
    }

    showNoMissingMessage() {
        const $tableBody = $('#translation-table tbody');
        const $existingMessage = $('#no-missing-message');

        if (!$existingMessage.length) {
            const colCount = $('#translation-table thead th').length;
            const messageRow = `
                <tr id="no-missing-message">
                    <td colspan="${colCount}" class="text-center">
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Great! All translation keys are complete.
                        </div>
                    </td>
                </tr>`;
            $tableBody.append(messageRow);
        }
    }

    hideNoMissingMessage() {
        $('#no-missing-message').remove();
    }

    initializeFileSelector() {
        const $fileCards = $('.file-card');
        const $selectedFileInput = $('#selected-file');
        const $form = $('#file-selection-form');

        if (!$fileCards.length) {
            return;
        }

        $fileCards.on('click', function () {
            const $card = $(this);
            $fileCards.removeClass('selected');
            $card.addClass('selected');

            const fileValue = $card.data('value');
            if ($selectedFileInput.length) {
                $selectedFileInput.val(fileValue);
            }

            if ($form.length) {
                $form.submit();
            }
        });
    }
}

class OverrideManager extends BaseAPI {
    constructor() {
        super();
        this.currentEditId = null;
        this.bindEvents();
        this.initializeSelect2();
    }

    bindEvents() {
        $('#save-override-btn').on('click', () => this.handleSave());
        $('#update-override-btn').on('click', () => this.handleUpdate());

        $(document).on('click', '.edit-override-btn', (e) => {
            this.fillEditModal($(e.currentTarget));
        });

        $(document).on('click', '.delete-override-btn', (e) => {
            this.confirmDelete($(e.currentTarget).data('id'));
        });

        $(document).on('show.bs.modal', '#add-override-modal', () => {
            this.clearAddModal();
        });

        $(document).on('shown.bs.modal', '#add-override-modal', () => {
            $('#override-locale').focus();
        });
    }

    initializeSelect2() {
        if ($('#override-key').length) {
            $('#override-key').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#add-override-modal'),
                placeholder: 'Search for a translation key ...',
                dropdownCssClass: 'select2-dropdown-with-spacing',
                minimumInputLength: 2,
                allowClear: true,
                tags: false,
                ajax: {
                    url: '/localization/overrides/search',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            query: params.term
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                escapeMarkup: function (markup) {
                    return markup;
                },
                templateResult: function (data) {
                    if (data.loading) {
                        return data.text;
                    }

                    let markup = '<div>';

                    markup += data.text;

                    markup += '<br><small class="text-muted">' + data.value + '</small>';

                    markup += '</div>';

                    return $(markup);
                },
                templateSelection: function (data) {
                    return data.text || data.id;
                }
            });
        }

        if ($('#override-locale').length) {
            $('#override-locale').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#add-override-modal'),
                placeholder: 'Select Language',
                allowClear: true,
                minimumResultsForSearch: 10,
                dropdownCssClass: 'select2-dropdown-with-spacing',
            });
        }

        $(document).on('select2:open', (e) => {
            $(".select2-search__field[aria-controls='select2-" + e.target.id + "-results']").each(function (key, value) {
                value.focus();
            });
        });
    }

    validate(locale, key, value) {
        if (!locale || !key || !value) {
            this.showToast('error', 'Language, key, and value are required.');
            return false;
        }
        return true;
    }

    handleSave() {
        const locale = $('#override-locale').val();
        const key = $('#override-key').val();
        const value = $('#override-value').val().trim();

        if (!this.validate(locale, key, value)) {
            return;
        }

        const $saveBtn = $('#save-override-btn');
        const originalHtml = $saveBtn.html();
        $saveBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

        $.ajax({
            url: '/localization/overrides/store',
            type: 'POST',
            data: {
                language: locale,
                key: key,
                value: value
            },
            success: (response) => {
                if (response.success) {
                    window.location.reload();
                } else {
                    this.showError(response);
                }
            },
            error: (error) => {
                this.handleError(error, 'Failed to add override');
            },
            complete: () => {
                $saveBtn.html(originalHtml).prop('disabled', false);
            }
        });
    }

    handleUpdate() {
        const id = this.currentEditId;
        const value = $('#edit-override-value').val().trim();

        if (!value) {
            this.showToast('error', 'Value is required.');
            return;
        }

        const $updateBtn = $('#update-override-btn');
        const originalHtml = $updateBtn.html();
        $updateBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);

        $.ajax({
            url: '/localization/overrides/update',
            type: 'POST',
            data: {
                id: id,
                value: value
            },
            success: (response) => {
                if (response.success) {
                    window.location.reload();
                } else {
                    this.showError(response);
                }
            },
            error: (error) => {
                this.handleError(error, 'Failed to update override');
            },
            complete: () => {
                $updateBtn.html(originalHtml).prop('disabled', false);
            }
        });
    }

    fillEditModal($btn) {
        this.currentEditId = $btn.data('id');
        $('#edit-override-value').val($btn.data('value'));

        new bootstrap.Modal($('#edit-override-modal')[0]).show();
    }

    confirmDelete(id) {
        Swal.fire({
            title: 'Delete Override?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'Cancel',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: '/localization/overrides/delete',
                type: 'DELETE',
                data: { id: id },
                success: (response) => {
                    if (response.success !== false) {
                        window.location.reload();
                    } else {
                        this.showError(response);
                    }
                },
                error: (error) => {
                    this.handleError(error, 'Failed to delete override');
                }
            });
        });
    }

    clearAddModal() {
        $('#override-locale').val('');
        $('#override-value').val('');
        $('#override-key').val(null).trigger('change');
    }

    showError(data) {
        let message = data.message || 'An error occurred';
        if (data.errors) {
            const errorMessages = [];
            $.each(data.errors, function (field, messages) {
                if (Array.isArray(messages)) {
                    $.each(messages, function (index, msg) {
                        errorMessages.push(msg);
                    });
                } else {
                    errorMessages.push(messages);
                }
            });
            message = errorMessages.join('<br>');
        }
        this.showToast('error', message);
    }
}

$(document).ready(function () {
    const localizationManager = new LocalizationManager();
    const overrideManager = new OverrideManager();
});