class LocalizationManager {
    constructor() {
        this.hasChanges = false;
        this.init();
    }
    init() {
        this.setupEventListeners();
        this.setupFormValidation();
        this.trackChanges();
        this.updateStats();
        this.initializeFilePicker();
        this.initializeDeleteButtons();
        this.initializeAddKeyModal();
    }
    initializeFilePicker() {
        const fileCards = document.querySelectorAll('.file-card:not(.status-file-card)');
        const hiddenInput = document.getElementById('selected-file');
        if (!fileCards.length) return;
        fileCards.forEach(card => {
            card.addEventListener('click', (e) => {
                e.preventDefault();
                const value = card.getAttribute('data-value');
                fileCards.forEach(c => {
                    c.classList.remove('selected');
                    const checkIcon = c.querySelector('.selected-check');
                    const addIcon = c.querySelector('.add-icon');
                    if (checkIcon) {
                        checkIcon.className = 'fas fa-circle-plus add-icon';
                    }
                });
                card.classList.add('selected');
                const addIcon = card.querySelector('.add-icon');
                if (addIcon) {
                    addIcon.className = 'fas fa-check-circle selected-check';
                }
                hiddenInput.value = value;
                setTimeout(() => {
                    const form = document.getElementById('file-selection-form');
                    if (form) {
                        form.submit();
                    }
                }, 300);
            });
        });
    }
    setupEventListeners() {
        document.addEventListener('DOMContentLoaded', () => {
            this.setupLoadingStates();
            this.setupModalHandlers();
            this.setupButtonHandlers();
        });
        window.addEventListener('beforeunload', (e) => {
            if (this.hasChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }
    setupButtonHandlers() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.save-changes-btn')) {
                this.saveChanges(e.target.closest('.save-changes-btn'));
            }
        });
        document.addEventListener('click', (e) => {
            if (e.target.closest('.modal-close')) {
                const modal = e.target.closest('.modal-close').dataset.modal;
                this.closeModal(modal);
            }
        });
    }
    setupLoadingStates() {
        const buttons = document.querySelectorAll('.btn[type="submit"], .btn[onclick]');
        buttons.forEach(button => {
            button.addEventListener('click', () => {
                if (!button.classList.contains('btn-secondary')) {
                    button.classList.add('loading');
                    setTimeout(() => {
                        button.classList.remove('loading');
                    }, 2000);
                }
            });
        });
    }
    setupModalHandlers() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            const closeBtn = modal.querySelector('.close-btn');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    this.closeModal(modal);
                });
            }
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modal);
                }
            });
        });
    }
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }
    closeModal(modal) {
        if (typeof modal === 'string') {
            modal = document.getElementById(modal);
        }
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
    setupFormValidation() {
        const forms = document.querySelectorAll('.needs-validation');
        forms.forEach(form => {
            form.addEventListener('submit', (event) => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    }
    trackChanges() {
        const textareas = document.querySelectorAll('.translation-textarea, .textarea-field');
        textareas.forEach(textarea => {
            const originalValue = textarea.value;
            textarea.addEventListener('input', () => {
                this.hasChanges = true;
                this.updateChangeIndicator();
                this.updateCharCount(textarea);
                this.updateStats();
            });
        });
    }
    updateChangeIndicator() {
        const indicator = document.getElementById('changes-indicator');
        if (indicator) {
            indicator.textContent = 'Unsaved changes';
            indicator.className = 'text-warning';
        }
    }
    updateCharCount(textarea) {
        const charCountEl = textarea.parentElement.querySelector('.char-count');
        if (charCountEl) {
            charCountEl.textContent = textarea.value.length;
        }
    }
    updateStats() {
        const completedCountEl = document.getElementById('completed-count');
        const missingCountEl = document.getElementById('missing-count');
        const totalCountEl = document.getElementById('total-count');
        if (completedCountEl && missingCountEl) {
            const textareas = document.querySelectorAll('.translation-textarea');
            const languageCount = document.querySelectorAll('thead th').length - 3; 
            if (textareas.length > 0 && languageCount > 0) {
                const totalKeys = textareas.length / languageCount;
                let completedKeys = 0;
                const keyGroups = {};
                textareas.forEach(textarea => {
                    const key = textarea.dataset.key;
                    if (!keyGroups[key]) {
                        keyGroups[key] = [];
                    }
                    keyGroups[key].push(textarea);
                });
                Object.keys(keyGroups).forEach(key => {
                    const hasAllTranslations = keyGroups[key].every(textarea => 
                        textarea.value.trim() !== ''
                    );
                    if (hasAllTranslations) {
                        completedKeys++;
                    }
                });
                const missingKeys = totalKeys - completedKeys;
                completedCountEl.textContent = completedKeys;
                missingCountEl.textContent = missingKeys;
                if (totalCountEl) {
                    totalCountEl.textContent = totalKeys;
                }
            }
            return;
        }
        const statsContainer = document.querySelector('.stats-container');
        if (!statsContainer) return;
        const textareas = document.querySelectorAll('.translation-textarea');
        const total = textareas.length;
        let completed = 0;
        textareas.forEach(textarea => {
            if (textarea.value.trim() !== '') {
                completed++;
            }
        });
        const missing = total - completed;
        const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        if (progressBar) progressBar.style.width = percentage + '%';
        if (progressText) progressText.textContent = percentage + '% Complete';
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
                this.updateChangeIndicator();
                this.showToast('Changes saved successfully', 'success');
            } else {
                throw new Error(data.message || 'Save failed');
            }
        })
        .catch(error => {
            console.error('Save failed:', error);
            this.showToast('Failed to save changes', 'error');
        })
        .finally(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
    resetForm() {
        if (confirm('Are you sure you want to reset all changes? This cannot be undone.')) {
            location.reload();
        }
    }
    showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `
            <div class="toast-content">
                <span class="toast-icon">${type === 'success' ? '✅' : '❌'}</span>
                <span class="toast-message">${message}</span>
            </div>
        `;
        container.appendChild(toast);
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
    filterTranslations(searchTerm) {
        const rows = document.querySelectorAll('#translation-table tbody tr');
        rows.forEach(row => {
            const key = row.querySelector('.translation-key').textContent.toLowerCase();
            const textareas = row.querySelectorAll('textarea');
            let hasMatch = key.includes(searchTerm.toLowerCase());
            textareas.forEach(textarea => {
                if (textarea.value.toLowerCase().includes(searchTerm.toLowerCase())) {
                    hasMatch = true;
                }
            });
            row.style.display = hasMatch ? '' : 'none';
        });
    }
    exportTranslations() {
        const form = document.getElementById('localization-form');
        const formData = new FormData(form);
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'translations.json';
        a.click();
        URL.revokeObjectURL(url);
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
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Please enter a translation key.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#3085d6',
                        zIndex: 9999
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
                zIndex: 9999
            }).then((result) => {
                if (result.isConfirmed) {
                    const row = document.getElementById(key);
                    if (row) {
                        row.remove();
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
}
const localizationManager = new LocalizationManager();
