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
        const { message, validationErrors } = this.parseError(error, defaultMessage);

        validationErrors
            ? this.showValidationErrors(validationErrors)
            : this.showToast('error', message);

        console.error('API Error:', error);
    }

    parseError(error, defaultMessage) {
        if (error.responseJSON) {
            return {
                message: error.responseJSON.message || defaultMessage,
                validationErrors: error.responseJSON.errors
            };
        }

        if (error.responseText) {
            try {
                const response = JSON.parse(error.responseText);
                return {
                    message: response.message || defaultMessage,
                    validationErrors: response.errors
                };
            } catch (e) {
                return {
                    message: error.statusText || defaultMessage,
                    validationErrors: null
                };
            }
        }

        return { message: defaultMessage, validationErrors: null };
    }

    showValidationErrors(errors) {
        const errorMessages = this.extractErrorMessages(errors);
        this.showToast('error', errorMessages.join('<br>'));
    }

    extractErrorMessages(errors) {
        if (typeof errors !== 'object') return [];

        return Object.values(errors).flatMap(messages =>
            Array.isArray(messages) ? messages : [messages]
        );
    }

    showToast(icon, message, timer = 3000) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer,
            timerProgressBar: true,
            didOpen: (toast) => {
                $(toast).on('mouseenter', () => Swal.stopTimer());
                $(toast).on('mouseleave', () => Swal.resumeTimer());
            }
        });

        return Toast.fire({ icon, html: message });
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

        if (!$form.length) return;

        const originalHtml = $button.html();
        this.setButtonLoading($button, 'Saving...');

        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: new FormData($form[0]),
            processData: false,
            contentType: false,
            success: () => this.showSuccess('Changes saved successfully'),
            error: (error) => this.handleError(error, 'Failed to save changes'),
            complete: () => this.resetButton($button, originalHtml)
        });
    }

    setButtonLoading($button, text) {
        $button
            .html(`<i class="fas fa-spinner fa-spin"></i> ${text}`)
            .prop('disabled', true);
    }

    resetButton($button, originalHtml) {
        $button
            .html(originalHtml)
            .prop('disabled', false);
    }

    initializeDeleteButtons() {
        $(document).on('click', '.delete-btn', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.confirmDelete($(e.currentTarget).closest('tr'));
            return false;
        });
    }

    initializeAddKeyModal() {
        const $addNewRowBtn = $('.add-new-row-btn');
        const $modal = $('#new-key-modal');
        const $keyInput = $('#key');

        if (!$addNewRowBtn.length) return;

        $addNewRowBtn.on('click', () => {
            const keyValue = $keyInput.val().trim();

            if (!keyValue) {
                this.showToast('error', 'Please enter a translation key.');
                return;
            }

            this.addNewRowToTable(keyValue);
            this.closeModal($modal);
            $keyInput.val('');
            this.scrollToNewRow(keyValue);
        });
    }

    closeModal($modal) {
        const modalInstance = bootstrap.Modal.getInstance($modal[0]) || new bootstrap.Modal($modal[0]);
        modalInstance.hide();
    }

    scrollToNewRow(keyValue) {
        setTimeout(() => {
            const $newRow = $(`#${keyValue}`);

            if ($newRow.length) {
                $('html, body').animate({
                    scrollTop: $newRow.offset().top - ($(window).height() / 2)
                }, 300);
            }
        }, 300);
    }

    confirmDelete($row) {
        const key = $row.attr('id');

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
                this.animateAndRemoveRow($row);
            }
        });
    }

    animateAndRemoveRow($row) {
        $row.css({
            'transition': 'opacity 0.3s ease, transform 0.3s ease',
            'opacity': '0',
            'transform': 'translateX(-20px)'
        });

        setTimeout(() => $row.remove(), 200);
    }

    addNewRowToTable(key) {
        const $tableBody = $('#translation-table tbody');
        const $languagesInput = $('input[name="languages"]');

        if (!$tableBody.length || !$languagesInput.length) return;

        const languages = JSON.parse($languagesInput.val());
        const rowCount = $tableBody.children().length + 1;

        $tableBody.append(this.buildNewRow(key, languages, rowCount));
    }

    buildNewRow(key, languages, rowCount) {
        const languageCells = languages.map(language =>
            this.buildLanguageCell(language, key)
        ).join('');

        return `
            <tr id="${key}">
                <td class="text-center align-middle">${rowCount}</td>
                <td class="align-middle">${key}</td>
                ${languageCells}
                <td class="text-center align-middle">
                    ${this.buildDeleteButton(key)}
                </td>
            </tr>`;
    }

    buildLanguageCell(language, key) {
        return `
            <td class="translation-cell">
                <div class="position-relative">
                    <textarea 
                        name="${language}[${key}]"
                        class="translation-textarea missing-field form-control" 
                        rows="3"
                        placeholder="Enter ${language.toUpperCase()} translation..."
                        data-language="${language}"
                        data-key="${key}"></textarea>
                </div>
            </td>`;
    }

    buildDeleteButton(key) {
        return `
            <button type="button" 
                class="btn btn-outline-danger btn-sm delete-btn"
                data-key="${key}" 
                title="Delete this translation key">
                <i class="fas fa-times"></i>
            </button>`;
    }

    initializeMissingKeyFilter() {
        const $showMissingBtn = $('#show-missing-btn');
        const $showAllBtn = $('#show-all-btn');
        const $filterStatus = $('#filter-status');

        if (!$showMissingBtn.length || !$showAllBtn.length) return;

        const totalRows = $('#translation-table tbody tr').length;

        if ($filterStatus.length) {
            $filterStatus.text(`Showing all ${totalRows} keys`);
        }

        $showMissingBtn.on('click', () => {
            this.showMissingKeysOnly();
            this.toggleFilterButtons($showMissingBtn, $showAllBtn);
        });

        $showAllBtn.on('click', () => {
            this.showAllKeys();
            this.toggleFilterButtons($showAllBtn, $showMissingBtn);
        });
    }

    toggleFilterButtons($hideBtn, $showBtn) {
        $hideBtn.addClass('d-none');
        $showBtn.removeClass('d-none');
    }

    showMissingKeysOnly() {
        const $rows = $('#translation-table tbody tr');
        const $filterStatus = $('#filter-status');
        let visibleCount = 0;

        $rows.each((index, row) => {
            const $row = $(row);
            const hasMissingFields = $row.find('.missing-field').length > 0;

            if (hasMissingFields) {
                $row.show();
                visibleCount++;
                $row.find('.text-center').first().text(visibleCount);
            } else {
                $row.hide();
            }
        });

        this.updateFilterStatus($filterStatus, visibleCount);

        if (visibleCount === 0) {
            this.showNoMissingMessage();
        }
    }

    updateFilterStatus($filterStatus, visibleCount) {
        if (!$filterStatus.length) return;

        const pluralText = visibleCount !== 1 ? 's' : '';
        $filterStatus.text(`Showing ${visibleCount} missing translation${pluralText}`);
    }

    showAllKeys() {
        const $rows = $('#translation-table tbody tr');
        const $filterStatus = $('#filter-status');

        $rows.each((index, row) => {
            const $row = $(row);
            $row.show();
            $row.find('.text-center').first().text(index + 1);
        });

        if ($filterStatus.length) {
            $filterStatus.text(`Showing all ${$rows.length} keys`);
        }

        this.hideNoMissingMessage();
    }

    showNoMissingMessage() {
        const $tableBody = $('#translation-table tbody');
        const $existingMessage = $('#no-missing-message');

        if ($existingMessage.length) return;

        const colCount = $('#translation-table thead th').length;
        $tableBody.append(this.buildNoMissingRow(colCount));
    }

    buildNoMissingRow(colCount) {
        return `
            <tr id="no-missing-message">
                <td colspan="${colCount}" class="text-center">
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Great! All translation keys are complete.
                    </div>
                </td>
            </tr>`;
    }

    hideNoMissingMessage() {
        $('#no-missing-message').remove();
    }

    initializeFileSelector() {
        const $fileCards = $('.file-card');
        const $selectedFileInput = $('#selected-file');
        const $form = $('#file-selection-form');

        if (!$fileCards.length) return;

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
        this.languages = [];
        this.urls = this.loadUrls();
        this.loadLanguages();
        this.bindEvents();
        this.initializeSelect2();
    }

    loadUrls() {
        return {
            search: $('#override-search-url').val() || '/localization/overrides/search',
            originalValues: $('#override-original-values-url').val() || '/localization/overrides/original-values',
            store: $('#override-store-url').val() || '/localization/overrides/store',
            update: $('#override-update-url').val() || '/localization/overrides/update',
            delete: $('#override-delete-url').val() || '/localization/overrides/delete'
        };
    }

    bindEvents() {
        this.bindSaveButton();
        this.bindUpdateButton();
        this.bindEditButtons();
        this.bindDeleteButtons();
        this.bindRemoveButtons();
        this.bindModalEvents();
        this.bindKeySearchEvents();
    }

    bindSaveButton() {
        $('#save-override-btn').on('click', () => this.handleSaveAll());
    }

    bindUpdateButton() {
        $('#update-override-btn').on('click', () => this.handleUpdate());
    }

    bindEditButtons() {
        $(document).on('click', '.edit-override-btn', (e) => {
            this.fillEditModal($(e.currentTarget));
        });
    }

    bindDeleteButtons() {
        $(document).on('click', '.delete-override-btn', (e) => {
            this.confirmDelete($(e.currentTarget).data('id'));
        });
    }

    bindRemoveButtons() {
        $(document).on('click', '.remove-modal-key-btn', (e) => {
            this.removeModalRow($(e.currentTarget));
        });
    }

    bindModalEvents() {
        $(document).on('show.bs.modal', '#add-override-modal', () => {
            this.clearAddModal();
        });

        $(document).on('shown.bs.modal', '#add-override-modal', () => {
            $('#override-key-search').focus();
        });
    }

    bindKeySearchEvents() {
        $('#override-key-search').on('select2:select', (e) => {
            const data = e.params.data;
            this.addKeyToModalTable(data.id);
            $('#override-key-search').val(null).trigger('change');
        });
    }

    loadLanguages() {
        const languagesData = $('#modal-languages-data').val();

        if (!languagesData) {
            this.languages = [];
            return;
        }

        try {
            const parsed = JSON.parse(languagesData);
            this.languages = this.parseLanguagesData(parsed);
        } catch (e) {
            console.error('Failed to parse languages data:', e);
            this.languages = [];
        }
    }

    parseLanguagesData(parsed) {
        if (Array.isArray(parsed)) return parsed;
        if (parsed && typeof parsed === 'object') return Object.values(parsed);
        return [];
    }

    initializeSelect2() {
        const $search = $('#override-key-search');

        if (!$search.length) return;

        $search.select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#add-override-modal'),
            placeholder: 'Search for a translation key...',
            dropdownCssClass: 'select2-dropdown-with-spacing',
            minimumInputLength: 2,
            allowClear: true,
            ajax: this.buildAjaxConfig(),
            escapeMarkup: (markup) => markup,
            templateResult: this.templateResult,
            templateSelection: (data) => data.text || data.id
        });

        this.bindSelect2Open();
    }

    buildAjaxConfig() {
        return {
            url: this.urls.search,
            dataType: 'json',
            delay: 250,
            data: (params) => ({ query: params.term }),
            processResults: (data) => ({ results: data }),
            cache: true
        };
    }

    templateResult(data) {
        if (data.loading) return data.text;

        return $(`
            <div>
                <strong>${data.text}</strong>
                ${data.value ? `<br><small class="text-muted">${data.value}</small>` : ''}
            </div>
        `);
    }

    bindSelect2Open() {
        $(document).on('select2:open', (e) => {
            $(`.select2-search__field[aria-controls='select2-${e.target.id}-results']`)
                .each((key, value) => value.focus());
        });
    }

    addKeyToModalTable(key) {
        if (this.isKeyAlreadyAdded(key)) {
            this.showToast('warning', 'This key is already added to the table');
            return;
        }

        $('#modal-no-keys-row').remove();
        this.fetchAndAddKey(key);
    }

    isKeyAlreadyAdded(key) {
        return $(`#modal-overrides-tbody tr[data-key="${key}"]`).length > 0;
    }

    fetchAndAddKey(key) {
        $.ajax({
            url: this.urls.originalValues,
            type: 'GET',
            data: { key },
            success: (response) => this.insertKeyRow(key, response.values),
            error: (error) => {
                console.error(`Failed to fetch original values for ${key}:`, error);
                this.insertKeyRow(key, this.buildEmptyValues());
            }
        });
    }

    buildEmptyValues() {
        return this.languages.reduce((acc, lang) => {
            acc[lang] = '';
            return acc;
        }, {});
    }

    insertKeyRow(key, originalValues) {
        if (!this.ensureLanguagesLoaded()) return;

        const row = this.buildKeyRow(key, originalValues);
        $('#modal-overrides-tbody').append(row);
    }

    ensureLanguagesLoaded() {
        if (!Array.isArray(this.languages) || this.languages.length === 0) {
            this.loadLanguages();
        }

        if (!Array.isArray(this.languages) || this.languages.length === 0) {
            this.showToast('error', 'Failed to load languages. Please refresh the page.');
            return false;
        }

        return true;
    }

    buildKeyRow(key, originalValues) {
        const languageCells = this.languages
            .map(language => this.buildLanguageTextarea(key, language, originalValues[language] || ''))
            .join('');

        return `
            <tr data-key="${key}">
                <td class="align-middle">
                    <code class="text-primary">${this.escapeHtml(key)}</code>
                </td>
                ${languageCells}
                <td class="text-center align-middle">
                    ${this.buildRemoveButton()}
                </td>
            </tr>`;
    }

    buildLanguageTextarea(key, language, originalValue) {
        return `
            <td>
                <textarea 
                    class="form-control modal-override-value" 
                    rows="2" 
                    data-key="${this.escapeHtml(key)}"
                    data-locale="${language}"
                    data-original="${this.escapeHtml(originalValue)}"
                    placeholder="${language.toUpperCase()}">${this.escapeHtml(originalValue)}</textarea>
            </td>`;
    }

    buildRemoveButton() {
        return `
            <button type="button" 
                class="btn btn-outline-danger btn-sm remove-modal-key-btn"
                title="Remove this key">
                <i class="fas fa-times"></i>
            </button>`;
    }

    removeModalRow($btn) {
        const $row = $btn.closest('tr');

        $row.fadeOut(200, () => {
            $row.remove();
            this.showNoKeysMessageIfEmpty();
        });
    }

    showNoKeysMessageIfEmpty() {
        if ($('#modal-overrides-tbody tr').length !== 0) return;

        const colspan = $('#modal-overrides-table thead th').length;
        $('#modal-overrides-tbody').html(this.buildNoKeysRow(colspan));
    }

    buildNoKeysRow(colspan) {
        return `
            <tr id="modal-no-keys-row">
                <td colspan="${colspan}" class="text-center text-muted py-4">
                    Search and select keys above to add them here
                </td>
            </tr>`;
    }

    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };

        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    handleSaveAll() {
        const overrides = this.collectOverrides();

        if (overrides.length === 0) {
            this.showToast('info', 'No changes to save');
            return;
        }

        this.saveOverrides(overrides);
    }

    collectOverrides() {
        const overrides = [];

        $('#modal-overrides-tbody .modal-override-value').each((index, element) => {
            const $textarea = $(element);
            const override = this.buildOverrideData($textarea);

            if (override) {
                overrides.push(override);
            }
        });

        return overrides;
    }

    buildOverrideData($textarea) {
        const key = $textarea.data('key');
        const locale = $textarea.data('locale');
        const value = $textarea.val().trim();
        const original = $textarea.data('original');

        if (!value || value === original) return null;

        return { key, locale, value };
    }

    saveOverrides(overrides) {
        const $saveBtn = $('#save-override-btn');
        const originalHtml = $saveBtn.html();

        this.setButtonLoading($saveBtn, 'Saving...');

        $.ajax({
            url: this.urls.store,
            type: 'POST',
            data: { overrides },
            success: (response) => this.handleSaveSuccess(response),
            error: (error) => this.handleError(error, 'Failed to save overrides'),
            complete: () => this.resetButton($saveBtn, originalHtml)
        });
    }

    handleSaveSuccess(response) {
        if (!response.success) {
            this.showError(response);
            return;
        }

        this.showSuccess(response.message || 'Overrides saved successfully');
        setTimeout(() => window.location.reload(), 1000);
    }

    setButtonLoading($button, text) {
        $button
            .html(`<i class="fas fa-spinner fa-spin"></i> ${text}`)
            .prop('disabled', true);
    }

    resetButton($button, originalHtml) {
        $button
            .html(originalHtml)
            .prop('disabled', false);
    }

    handleUpdate() {
        const id = this.currentEditId;
        const value = $('#edit-override-value').val().trim();

        if (!value) {
            this.showToast('error', 'Value is required.');
            return;
        }

        this.updateOverride(id, value);
    }

    updateOverride(id, value) {
        const $updateBtn = $('#update-override-btn');
        const originalHtml = $updateBtn.html();

        this.setButtonLoading($updateBtn, 'Updating...');

        $.ajax({
            url: this.urls.update,
            type: 'POST',
            data: { id, value },
            success: (response) => this.handleUpdateSuccess(response),
            error: (error) => this.handleError(error, 'Failed to update override'),
            complete: () => this.resetButton($updateBtn, originalHtml)
        });
    }

    handleUpdateSuccess(response) {
        response.success
            ? window.location.reload()
            : this.showError(response);
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
            if (result.isConfirmed) {
                this.deleteOverride(id);
            }
        });
    }

    deleteOverride(id) {
        $.ajax({
            url: this.urls.delete,
            type: 'DELETE',
            data: { id },
            success: (response) => this.handleDeleteSuccess(response),
            error: (error) => this.handleError(error, 'Failed to delete override')
        });
    }

    handleDeleteSuccess(response) {
        response.success !== false
            ? window.location.reload()
            : this.showError(response);
    }

    clearAddModal() {
        $('#override-key-search').val(null).trigger('change');

        const colspan = $('#modal-overrides-table thead th').length;
        $('#modal-overrides-tbody').html(this.buildNoKeysRow(colspan));
    }

    showError(data) {
        const message = this.buildErrorMessage(data);
        this.showToast('error', message);
    }

    buildErrorMessage(data) {
        if (!data.errors) {
            return data.message || 'An error occurred';
        }

        const errorMessages = Object.values(data.errors).flatMap(messages =>
            Array.isArray(messages) ? messages : [messages]
        );

        return errorMessages.join('<br>');
    }
}

$(() => {
    new LocalizationManager();
    new OverrideManager();
});