class LocalizationManager {
    constructor() {
        this.hasChanges = false;
        this.init();
    }

    init() {
        this.initializeFormTracking();
        this.setupButtonHandlers();
        this.initializeDeleteButtons();
        this.initializeAddKeyModal();
        this.initializeMissingKeyFilter();
        this.initializeOverrides();
        this.initializeFileSelector();
    }

    initializeFormTracking() {
        const form = document.getElementById('localization-form');
        if (!form) return;

        const inputs = form.querySelectorAll('textarea');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                this.hasChanges = true;
                this.updateFieldStatus(input);
            });
        });

        window.addEventListener('beforeunload', (e) => {
            if (this.hasChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }

    updateFieldStatus(field) {
        const indicator = field.parentElement.querySelector('.status-indicator');
        if (!indicator) return;

        if (field.value.trim()) {
            field.classList.remove('missing-field');
            field.classList.add('filled-field');
            indicator.classList.remove('missing');
            indicator.classList.add('filled');
            indicator.title = 'Translation provided';
        } else {
            field.classList.remove('filled-field');
            field.classList.add('missing-field');
            indicator.classList.remove('filled');
            indicator.classList.add('missing');
            indicator.title = 'Missing translation';
        }
    }

    setupButtonHandlers() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.save-changes-btn')) {
                this.saveChanges(e.target.closest('.save-changes-btn'));
            }
        });
    }

    saveChanges(button) {
        const form = document.getElementById('localization-form');
        if (!form) return;

        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        button.disabled = true;

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    this.hasChanges = false;
                    Swal.fire({
                        title: 'Success!',
                        text: 'Changes saved successfully',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                } else {
                    throw new Error(data.message || 'Save failed');
                }
            })
            .catch(error => {
                console.error('Save failed:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to save changes',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3085d6',
                });
            })
            .finally(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            });
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
        if (typeof Swal !== 'undefined') {
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

        // Initialize filter status on page load
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

    initializeOverrides() {
        this.initializeOverrideModals();
        this.initializeOverrideSearch();
        this.initializeOverrideActions();
    }

    initializeOverrideModals() {
        const saveBtn = document.getElementById('save-override-btn');
        const updateBtn = document.getElementById('update-override-btn');

        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveOverride());
        }

        if (updateBtn) {
            updateBtn.addEventListener('click', () => this.updateOverride());
        }

        document.addEventListener('click', (e) => {
            if (e.target.closest('.edit-override-btn')) {
                const key = e.target.closest('.edit-override-btn').getAttribute('data-key');
                this.editOverride(key);
            }
        });

        document.addEventListener('click', (e) => {
            if (e.target.closest('.delete-override-btn')) {
                const key = e.target.closest('.delete-override-btn').getAttribute('data-key');
                this.deleteOverride(key);
            }
        });
    }

    initializeOverrideSearch() {
        const searchInput = document.getElementById('search-input');
        const clearBtn = document.getElementById('clear-search');

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filterOverrides(e.target.value);
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                this.filterOverrides('');
            });
        }
    }

    initializeOverrideActions() {
        document.addEventListener('show.bs.modal', (e) => {
            if (e.target.id === 'add-override-modal') {
                this.clearAddModal();
            }
        });
    }

    saveOverride() {
        const key = document.getElementById('override-key').value.trim();
        if (!key) {
            Swal.fire('Error!', 'Please enter a translation key.', 'error');
            return;
        }

        const translations = {};
        document.querySelectorAll('.translations-container textarea').forEach(textarea => {
            const locale = textarea.name.replace('translations[', '').replace(']', '');
            if (textarea.value.trim()) {
                translations[locale] = textarea.value.trim();
            }
        });

        if (Object.keys(translations).length === 0) {
            Swal.fire('Error!', 'Please provide at least one translation.', 'error');
            return;
        }

        this.submitOverride('store', key, translations);
    }

    clearAddModal() {
        document.getElementById('override-key').value = '';
        document.querySelectorAll('.translations-container textarea').forEach(textarea => {
            textarea.value = '';
        });
    }

    filterOverrides(query) {
        const rows = document.querySelectorAll('#overrides-table tbody tr[data-key]');
        let visibleCount = 0;

        rows.forEach(row => {
            const key = row.getAttribute('data-key');
            const values = Array.from(row.querySelectorAll('.translation-value')).map(el => el.textContent);

            const matches = key.toLowerCase().includes(query.toLowerCase()) ||
                values.some(value => value.toLowerCase().includes(query.toLowerCase()));

            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('total-count').textContent = visibleCount;
    }

    editOverride(key) {
        const row = document.querySelector(`tr[data-key="${key}"]`);
        if (!row) return;

        const translations = {};

        row.querySelectorAll('.translation-value').forEach((cell, index) => {
            const locales = Array.from(document.querySelectorAll('#overrides-table thead .language-badge'))
                .map(badge => badge.textContent.toLowerCase());

            if (locales[index] && cell.textContent.trim() && cell.textContent.trim() !== '—') {
                translations[locales[index]] = cell.getAttribute('title') || cell.textContent.trim();
            }
        });

        document.getElementById('edit-override-key').value = key;

        document.querySelectorAll('#edit-override-modal .translations-container textarea').forEach(textarea => {
            const locale = textarea.name.replace('translations[', '').replace(']', '');
            textarea.value = translations[locale] || '';
        });

        const modal = new bootstrap.Modal(document.getElementById('edit-override-modal'));
        modal.show();
    }

    updateOverride() {
        const key = document.getElementById('edit-override-key').value;
        const translations = {};

        document.querySelectorAll('#edit-override-modal .translations-container textarea').forEach(textarea => {
            const locale = textarea.name.replace('translations[', '').replace(']', '');
            if (textarea.value.trim()) {
                translations[locale] = textarea.value.trim();
            }
        });

        this.submitOverride('update', key, translations);
    }

    deleteOverride(key) {
        Swal.fire({
            title: 'Delete Translation Override?',
            text: `Are you sure you want to delete the override for "${key}"? This cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('/localization/overrides/delete', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ key: key })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.querySelector(`tr[data-key="${key}"]`).remove();
                            this.updateEmptyState();
                            Swal.fire('Deleted!', data.message, 'success');
                        } else {
                            Swal.fire('Error!', data.message, 'error');
                        }
                    });
            }
        });
    }

    submitOverride(action, key, translations) {
        const url = action === 'store' ? '/localization/overrides/store' : '/localization/overrides/update';
        const method = 'POST';

        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ key: key, translations: translations })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success!', data.message, 'success');

                    const modalId = action === 'store' ? 'add-override-modal' : 'edit-override-modal';
                    const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
                    modal.hide();

                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Submit failed:', error);
                Swal.fire('Error!', 'Failed to save override', 'error');
            });
    }

    updateEmptyState() {
        const tbody = document.querySelector('#overrides-table tbody');
        const dataRows = tbody.querySelectorAll('tr[data-key]');

        if (dataRows.length === 0) {
            tbody.innerHTML = `
                <tr id="no-overrides-row">
                    <td colspan="100%" class="text-center py-4">
                        <div class="empty-state">
                            <i class="fas fa-inbox text-muted mb-2" style="font-size: 2rem;"></i>
                            <p class="text-muted mb-0">No translation overrides found</p>
                            <p class="small text-muted">Click "Add Translation Override" to create your first override</p>
                        </div>
                    </td>
                </tr>
            `;
        }

        document.getElementById('total-count').textContent = dataRows.length;
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

const localizationManager = new LocalizationManager();