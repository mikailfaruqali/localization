class BaseAPI {
    constructor() {
        this.setupAxios();
    }

    setupAxios() {
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        axios.defaults.headers.common['X-CSRF-TOKEN'] = this.getCsrfToken();
        axios.defaults.headers.common['Accept'] = 'application/json';
    }

    getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute('content') : '';
    }

    handleError(error, defaultMessage = 'An error occurred') {
        const message = error.response?.data?.message || error.message || defaultMessage;

        Swal.fire({
            title: 'Error!',
            text: message,
            icon: 'error',
            confirmButtonText: 'OK',
            confirmButtonColor: '#3085d6',
        });

        console.error('API Error:', error);
    }

    showSuccess(message = 'Operation completed successfully', timer = 2000) {
        return Swal.fire({
            title: 'Success!',
            text: message,
            icon: 'success',
            timer: timer,
            showConfirmButton: false,
        });
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
        document.addEventListener('click', (e) => {
            if (e.target.closest('.save-changes-btn')) {
                this.saveChanges(e.target.closest('.save-changes-btn'));
            }
        });
    }

    async saveChanges(button) {
        const form = document.getElementById('localization-form');
        if (!form) return;

        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        button.disabled = true;

        try {
            const formData = new FormData(form);
            await axios.post(form.action, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });

            await this.showSuccess('Changes saved successfully');
        } catch (error) {
            this.handleError(error, 'Failed to save changes');
        } finally {
            button.innerHTML = originalText;
            button.disabled = false;
        }
    }

    initializeDeleteButtons() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.delete-btn')) {
                e.preventDefault();
                e.stopPropagation();
                const button = e.target.closest('.delete-btn');
                const key = button.getAttribute('data-key');
                this.confirmDelete(key);
                return false;
            }
        });
    }

    initializeAddKeyModal() {
        const addNewRowBtn = document.querySelector('.add-new-row-btn');
        const modal = document.getElementById('new-key-modal');
        const keyInput = document.getElementById('key');

        if (addNewRowBtn) {
            addNewRowBtn.addEventListener('click', () => {
                const keyValue = keyInput.value.trim();

                if (keyValue) {
                    this.addNewRowToTable(keyValue);

                    const modalInstance = bootstrap.Modal.getInstance(modal) || new bootstrap.Modal(modal);
                    modalInstance.hide();

                    keyInput.value = '';

                    setTimeout(() => {
                        const newRow = document.getElementById(keyValue);
                        if (newRow) {
                            newRow.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                            newRow.style.backgroundColor = '#fff3cd';
                            setTimeout(() => {
                                newRow.style.backgroundColor = '';
                            }, 2000);
                        }
                    }, 300);
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Please enter a translation key.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#3085d6',
                    });
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
                const row = document.getElementById(key);
                if (row) {
                    row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-20px)';

                    setTimeout(() => {
                        row.remove();
                    }, 300);
                }
            }
        });
    }

    addNewRowToTable(key) {
        const tableBody = document.querySelector('#translation-table tbody');
        const languagesInput = document.querySelector('input[name="languages"]');

        if (!tableBody || !languagesInput) return;

        const languages = JSON.parse(languagesInput.value);
        const rowCount = tableBody.children.length + 1;

        let newRow = `
            <tr id="${key}" class="translation-row">
                <td class="text-center align-middle">${rowCount}</td>
                <td class="align-middle">
                    <div class="key-container">
                        <code class="translation-key">${key}</code>
                    </div>
                </td>`;

        languages.forEach(language => {
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

        tableBody.insertAdjacentHTML('beforeend', newRow);
    }

    initializeMissingKeyFilter() {
        const showMissingBtn = document.getElementById('show-missing-btn');
        const showAllBtn = document.getElementById('show-all-btn');
        const filterStatus = document.getElementById('filter-status');

        if (!showMissingBtn || !showAllBtn) return;

        const totalRows = document.querySelectorAll('#translation-table tbody tr').length;
        if (filterStatus) {
            filterStatus.textContent = `Showing all ${totalRows} keys`;
        }

        showMissingBtn.addEventListener('click', () => {
            this.showMissingKeysOnly();
            showMissingBtn.classList.add('d-none');
            showAllBtn.classList.remove('d-none');
        });

        showAllBtn.addEventListener('click', () => {
            this.showAllKeys();
            showAllBtn.classList.add('d-none');
            showMissingBtn.classList.remove('d-none');
        });
    }

    showMissingKeysOnly() {
        const rows = document.querySelectorAll('#translation-table tbody tr');
        const filterStatus = document.getElementById('filter-status');
        let visibleCount = 0;

        rows.forEach((row, index) => {
            const hasMissingFields = row.querySelector('.bg-danger') || row.querySelector('.missing-field');
            if (hasMissingFields) {
                row.style.display = '';
                visibleCount++;
                row.querySelector('.text-center').textContent = visibleCount;
            } else {
                row.style.display = 'none';
            }
        });

        if (filterStatus) {
            filterStatus.textContent = `Showing ${visibleCount} missing translation${visibleCount !== 1 ? 's' : ''}`;
        }

        if (visibleCount === 0) {
            this.showNoMissingMessage();
        }
    }

    showAllKeys() {
        const rows = document.querySelectorAll('#translation-table tbody tr');
        const filterStatus = document.getElementById('filter-status');

        rows.forEach((row, index) => {
            row.style.display = '';
            row.querySelector('.text-center').textContent = index + 1;
        });

        if (filterStatus) {
            filterStatus.textContent = `Showing all ${rows.length} keys`;
        }

        this.hideNoMissingMessage();
    }

    showNoMissingMessage() {
        const tableBody = document.querySelector('#translation-table tbody');
        const existingMessage = document.getElementById('no-missing-message');

        if (!existingMessage) {
            const colCount = document.querySelectorAll('#translation-table thead th').length;
            const messageRow = document.createElement('tr');
            messageRow.id = 'no-missing-message';
            messageRow.innerHTML = `
                <td colspan="${colCount}" class="text-center">
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Great! All translation keys are complete.
                    </div>
                </td>
            `;
            tableBody.appendChild(messageRow);
        }
    }

    hideNoMissingMessage() {
        const message = document.getElementById('no-missing-message');
        if (message) {
            message.remove();
        }
    }

    initializeFileSelector() {
        const fileCards = document.querySelectorAll('.file-card');
        const selectedFileInput = document.getElementById('selected-file');
        const form = document.getElementById('file-selection-form');

        if (!fileCards.length) return;

        fileCards.forEach(card => {
            card.addEventListener('click', () => {
                fileCards.forEach(c => c.classList.remove('selected'));
                card.classList.add('selected');

                const fileValue = card.getAttribute('data-value');
                if (selectedFileInput) {
                    selectedFileInput.value = fileValue;
                }

                if (form) {
                    form.submit();
                }
            });
        });
    }
}

class OverrideManager extends BaseAPI {
    constructor() {
        super();
        this.currentEditId = null;
        this.bindEvents();
    }

    bindEvents() {
        document.getElementById('save-override-btn')?.addEventListener('click', () => this.handleSave());
        document.getElementById('update-override-btn')?.addEventListener('click', () => this.handleUpdate());

        document.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.edit-override-btn');
            const delBtn = e.target.closest('.delete-override-btn');

            if (editBtn) this.fillEditModal(editBtn);
            if (delBtn) this.confirmDelete(delBtn.dataset.id);
        });

        document.addEventListener('show.bs.modal', (e) => {
            if (e.target.id === 'add-override-modal') this.clearAddModal();
        });
    }

    validate(locale, key, value) {
        if (!locale || !key || !value) {
            Swal.fire('Validation Error', 'Language, key, and value are required.', 'error');
            return false;
        }
        return true;
    }

    async handleSave() {
        const locale = document.getElementById('override-locale').value.trim();
        const key = document.getElementById('override-key').value.trim();
        const value = document.getElementById('override-value').value.trim();

        if (!this.validate(locale, key, value)) return;

        try {
            const response = await axios.post('/localization/overrides/store', {
                language: locale,
                key: key,
                value: value
            });

            if (response.data.success) {
                bootstrap.Modal.getInstance(document.getElementById('add-override-modal')).hide();
                setTimeout(() => window.location.reload(), 300);
            } else {
                this.showError(response.data);
            }
        } catch (error) {
            this.handleError(error);
        }
    }

    async handleUpdate() {
        const id = this.currentEditId;
        const locale = document.getElementById('edit-override-locale').value.trim();
        const key = document.getElementById('edit-override-key').value.trim();
        const value = document.getElementById('edit-override-value').value.trim();

        if (!this.validate(locale, key, value)) return;

        try {
            const response = await axios.post('/localization/overrides/update', {
                id: id,
                language: locale,
                key: key,
                value: value
            });

            if (response.data.success) {
                bootstrap.Modal.getInstance(document.getElementById('edit-override-modal')).hide();
                setTimeout(() => window.location.reload(), 300);
            } else {
                this.showError(response.data);
            }
        } catch (error) {
            this.handleError(error);
        }
    }

    fillEditModal(btn) {
        this.currentEditId = btn.dataset.id;
        document.getElementById('edit-override-locale').value = btn.dataset.locale;
        document.getElementById('edit-override-key').value = btn.dataset.key;
        document.getElementById('edit-override-value').value = btn.dataset.value;

        new bootstrap.Modal(document.getElementById('edit-override-modal')).show();
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
        }).then(async (result) => {
            if (!result.isConfirmed) return;

            try {
                const response = await axios.delete('/localization/overrides/delete', {
                    data: { id: id }
                });

                if (response.data?.success !== false) {
                    setTimeout(() => window.location.reload(), 300);
                } else {
                    this.showError(response.data);
                }
            } catch (error) {
                this.handleError(error);
            }
        });
    }

    clearAddModal() {
        ['override-locale', 'override-key', 'override-value'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
    }

    showError(data) {
        let message = data.message || 'An error occurred';
        if (data.errors) message = Object.values(data.errors).flat().join('\n');
        Swal.fire('Error!', message, 'error');
    }
}

const localizationManager = new LocalizationManager();
const overrideManager = new OverrideManager();