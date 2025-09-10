class LocalizationManager {
    constructor() {
        this.hasChanges = false;
        this.init();
    }
    init() {
        this.setupEventListeners();
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
                    zIndex: 9999
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
                zIndex: 9999
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
}
const localizationManager = new LocalizationManager();
