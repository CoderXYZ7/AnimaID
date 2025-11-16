// public/js/pages/animators.js

import { apiService } from '../apiService.js';
import { ui } from '../ui.js';

let state = {
    currentUser: null,
    currentPage: 1,
    totalPages: 1,
    currentFilters: {},
    currentAnimatorId: null,
    tempDocuments: [],
    tempNotes: [],
};

// Initialization
document.addEventListener('DOMContentLoaded', initPage);

async function initPage() {
    ui.showLoadingScreen();
    try {
        const userData = localStorage.getItem('animaid_user');
        if (!userData) {
            window.location.href = '../login.html';
            return;
        }
        state.currentUser = JSON.parse(userData);

        await apiService.getSelf(); // Verify token

        const hasPermission = await checkAnimatorsPermission();
        if (!hasPermission) {
            ui.showErrorScreen();
            return;
        }

        await loadAnimators();
        addEventListeners();
        ui.showMainContent();
    } catch (error) {
        console.error('Page initialization error:', error);
        localStorage.removeItem('animaid_token');
        localStorage.removeItem('animaid_user');
        window.location.href = '../login.html';
    } finally {
        ui.hideLoadingScreen();
    }
}

async function checkAnimatorsPermission() {
    try {
        await apiService.getAnimators({ page: 1, limit: 1 });
        return true;
    } catch (error) {
        return false;
    }
}

// Data loading
async function loadAnimators(page = 1, filters = {}) {
    try {
        const data = await apiService.getAnimators({ page, limit: 10, ...filters });
        ui.renderAnimatorsList(data.animators);
        ui.renderPagination(data.pagination);
        state.currentPage = page;
        state.totalPages = data.pagination.pages;
        state.currentFilters = filters;
    } catch (error) {
        console.error('Error loading animators:', error);
        ui.showToast('Error loading animators', 'error');
    }
}

// Event Listeners
function addEventListeners() {
    document.getElementById('logout-btn').addEventListener('click', async () => {
        try {
            await apiService.logout();
        } catch (error) {
            console.error('Logout error:', error);
        }
        localStorage.removeItem('animaid_token');
        localStorage.removeItem('animaid_user');
        window.location.href = '../login.html';
    });

    document.getElementById('add-animator-btn').addEventListener('click', () => {
        openAnimatorModal();
    });

    document.getElementById('filter-btn').addEventListener('click', () => {
        const filters = {
            search: document.getElementById('search-input').value.trim(),
            status: document.getElementById('status-filter').value,
            specialization: document.getElementById('specialization-filter').value.trim(),
        };
        loadAnimators(1, Object.fromEntries(Object.entries(filters).filter(([, v]) => v)));
    });

    document.getElementById('clear-filters-btn').addEventListener('click', () => {
        document.getElementById('search-input').value = '';
        document.getElementById('status-filter').value = '';
        document.getElementById('specialization-filter').value = '';
        loadAnimators(1, {});
    });

    document.getElementById('prev-page').addEventListener('click', () => {
        if (state.currentPage > 1) {
            loadAnimators(state.currentPage - 1, state.currentFilters);
        }
    });

    document.getElementById('next-page').addEventListener('click', () => {
        if (state.currentPage < state.totalPages) {
            loadAnimators(state.currentPage + 1, state.currentFilters);
        }
    });

    // Animator Modal
    document.getElementById('close-modal').addEventListener('click', closeAnimatorModal);
    document.getElementById('cancel-btn').addEventListener('click', closeAnimatorModal);
    document.getElementById('save-animator-btn').addEventListener('click', saveAnimator);

    // Document Modal
    document.getElementById('add-document-btn').addEventListener('click', openDocumentModal);
    document.getElementById('close-document-modal').addEventListener('click', closeDocumentModal);
    document.getElementById('cancel-document-btn').addEventListener('click', closeDocumentModal);
    document.getElementById('save-document-btn').addEventListener('click', saveDocument);
    document.getElementById('document-file').addEventListener('change', handleFileSelection);

    // Note Modal
    document.getElementById('add-note-btn').addEventListener('click', openNoteModal);
    document.getElementById('close-note-modal').addEventListener('click', closeNoteModal);
    document.getElementById('cancel-note-btn').addEventListener('click', closeNoteModal);
    document.getElementById('save-note-btn').addEventListener('click', saveNote);

    // Link User
    document.getElementById('add-user-btn').addEventListener('click', showLinkUserModal);
}


// Animator Modal
function openAnimatorModal(animator = null) {
    const modal = document.getElementById('animator-modal');
    const modalTitle = document.getElementById('modal-title');
    const form = document.getElementById('basic-form');

    if (animator) {
        modalTitle.textContent = 'Edit Animator';
        state.currentAnimatorId = animator.id;
        form.elements.first_name.value = animator.first_name;
        form.elements.last_name.value = animator.last_name;
        form.elements.birth_date.value = animator.birth_date || '';
        form.elements.gender.value = animator.gender || '';
        form.elements.hire_date.value = animator.hire_date || '';
        form.elements.nationality.value = animator.nationality || '';
        form.elements.language.value = animator.language || '';
        form.elements.education.value = animator.education || '';
        form.elements.specialization.value = animator.specialization || '';
        form.elements.address.value = animator.address || '';
        form.elements.phone.value = animator.phone || '';
        form.elements.email.value = animator.email || '';
        form.elements.status.value = animator.status || 'active';
    } else {
        modalTitle.textContent = 'Add New Animator';
        state.currentAnimatorId = null;
        form.reset();
        state.tempDocuments = [];
        state.tempNotes = [];
    }

    document.getElementById('tab-basic').click();
    modal.classList.remove('hidden');
}

function closeAnimatorModal() {
    document.getElementById('animator-modal').classList.add('hidden');
}

async function saveAnimator() {
    const form = document.getElementById('basic-form');
    const formData = new FormData(form);
    const animatorData = Object.fromEntries(formData.entries());

    try {
        let savedAnimator;
        if (state.currentAnimatorId) {
            savedAnimator = await apiService.updateAnimator(state.currentAnimatorId, animatorData);
        } else {
            savedAnimator = await apiService.createAnimator(animatorData);
            const animatorId = savedAnimator.animator_id;

            for (const doc of state.tempDocuments) {
                await apiService.uploadDocument(animatorId, doc);
            }
            for (const note of state.tempNotes) {
                await apiService.createNote(animatorId, note);
            }
        }
        closeAnimatorModal();
        loadAnimators(state.currentPage, state.currentFilters);
        ui.showToast(`Animator ${state.currentAnimatorId ? 'updated' : 'created'} successfully!`);
    } catch (error) {
        console.error('Error saving animator:', error);
        ui.showToast('Error saving animator', 'error');
    }
}

// Global functions for inline event handlers
window.viewAnimator = async (animatorId) => {
    try {
        const animator = await apiService.getAnimator(animatorId);
        openAnimatorModal(animator.animator);
    } catch (error) {
        console.error('Error loading animator for view:', error);
        ui.showToast('Error loading animator details', 'error');
    }
};

window.editAnimator = async (animatorId) => {
    try {
        const animator = await apiService.getAnimator(animatorId);
        openAnimatorModal(animator.animator);
    } catch (error) {
        console.error('Error loading animator for edit:', error);
        ui.showToast('Error loading animator details', 'error');
    }
};

window.deleteAnimator = async (animatorId) => {
    if (!confirm('Are you sure you want to delete this animator record?')) return;

    try {
        await apiService.deleteAnimator(animatorId);
        loadAnimators(state.currentPage, state.currentFilters);
        ui.showToast('Animator deleted successfully!');
    } catch (error) {
        console.error('Error deleting animator:', error);
        ui.showToast('Error deleting animator', 'error');
    }
};

// Tabs
document.querySelectorAll('.tab-button').forEach(button => {
    button.addEventListener('click', function() {
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active', 'border-blue-500', 'text-blue-600'));
        this.classList.add('active', 'border-blue-500', 'text-blue-600');
        document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
        document.getElementById(`${this.id.replace('tab-', '')}-tab`).classList.remove('hidden');

        if (state.currentAnimatorId) {
            const tabId = this.id;
            if (tabId === 'tab-users') loadLinkedUsers(state.currentAnimatorId);
            if (tabId === 'tab-documents') loadDocuments(state.currentAnimatorId);
            if (tabId === 'tab-notes') loadNotes(state.currentAnimatorId);
        }
    });
});

// Linked Users
async function loadLinkedUsers(animatorId) {
    try {
        const data = await apiService.getLinkedUsers(animatorId);
        ui.renderLinkedUsersList(data.users);
    } catch (error) {
        console.error('Error loading linked users:', error);
        ui.showToast('Error loading linked users', 'error');
    }
}

async function showLinkUserModal() {
    const users = await apiService.getUsers();
    
    if (users.users.length === 0) {
        ui.showToast('No users available to link', 'error');
        return;
    }

    const modalHtml = `
        <div id="link-user-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Link User to Animator</h3>
                    <button id="close-link-user-modal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form id="link-user-form" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Select User *</label>
                        <select id="user-select" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Choose a user...</option>
                            ${users.users.map(user => `<option value="${user.id}">${user.username} (${user.email})</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Relationship Type *</label>
                        <select id="relationship-type" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="primary">Primary</option>
                            <option value="secondary">Secondary</option>
                            <option value="backup">Backup</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea id="link-notes" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Add any notes about this relationship..."></textarea>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button" id="cancel-link-user-modal" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg font-medium">
                            Cancel
                        </button>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-link mr-2"></i>Link User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    document.getElementById('close-link-user-modal').addEventListener('click', hideLinkUserModal);
    document.getElementById('cancel-link-user-modal').addEventListener('click', hideLinkUserModal);

    document.getElementById('link-user-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        await linkUser();
    });
}

function hideLinkUserModal() {
    const modal = document.getElementById('link-user-modal');
    if (modal) {
        modal.remove();
    }
}

async function linkUser() {
    try {
        const userId = document.getElementById('user-select').value;
        const relationshipType = document.getElementById('relationship-type').value;
        const notes = document.getElementById('link-notes').value;

        if (!userId) {
            ui.showToast('Please select a user', 'error');
            return;
        }

        await apiService.linkUser(state.currentAnimatorId, {
            user_id: parseInt(userId),
            relationship_type: relationshipType,
            notes: notes
        });

        hideLinkUserModal();
        await loadLinkedUsers(state.currentAnimatorId);
        ui.showToast('User linked successfully!');
    } catch (error) {
        console.error('Error linking user:', error);
        if (error.message.includes('This user is already a primary user for another animator')) {
            ui.showToast('This user is already a primary user for another animator.', 'error');
        } else {
            ui.showToast('Error linking user', 'error');
        }
    }
}

window.unlinkUser = async (userId) => {
    if (!confirm('Are you sure you want to unlink this user?')) return;
    try {
        await apiService.unlinkUser(state.currentAnimatorId, userId);
        loadLinkedUsers(state.currentAnimatorId);
        ui.showToast('User unlinked successfully!');
    } catch (error) {
        console.error('Error unlinking user:', error);
        ui.showToast('Error unlinking user', 'error');
    }
};

// Documents
function openDocumentModal() {
    document.getElementById('document-form').reset();
    document.getElementById('file-name').classList.add('hidden');
    document.getElementById('document-modal').classList.remove('hidden');
}

function closeDocumentModal() {
    document.getElementById('document-modal').classList.add('hidden');
}

function handleFileSelection(event) {
    const file = event.target.files[0];
    const fileNameElement = document.getElementById('file-name');

    if (file) {
        fileNameElement.textContent = `Selected: ${file.name}`;
        fileNameElement.classList.remove('hidden');
    } else {
        fileNameElement.classList.add('hidden');
    }
}

async function saveDocument() {
    const form = document.getElementById('document-form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);

    if (!state.currentAnimatorId) {
        state.tempDocuments.push(formData);
        closeDocumentModal();
        ui.showToast('Document will be saved with the new animator.');
        return;
    }

    try {
        await apiService.uploadDocument(state.currentAnimatorId, formData);
        closeDocumentModal();
        loadDocuments(state.currentAnimatorId);
        ui.showToast('Document uploaded successfully!');
    } catch (error) {
        console.error('Error uploading document:', error);
        ui.showToast('Error uploading document', 'error');
    }
}

async function loadDocuments(animatorId) {
    try {
        const data = await apiService.getDocuments(animatorId);
        ui.renderDocumentsList(data.documents, animatorId);
    } catch (error) {
        console.error('Error loading documents:', error);
        ui.showToast('Error loading documents', 'error');
    }
}

window.deleteDocument = async (animatorId, documentId) => {
    if (!confirm('Are you sure you want to delete this document?')) return;
    try {
        await apiService.deleteDocument(animatorId, documentId);
        loadDocuments(animatorId);
        ui.showToast('Document deleted successfully!');
    } catch (error) {
        console.error('Error deleting document:', error);
        ui.showToast('Error deleting document', 'error');
    }
};

window.downloadDocument = async (documentId, animatorId) => {
    try {
        const response = await apiService.downloadDocument(animatorId, documentId);
        const blob = await response.blob();
        const contentDisposition = response.headers.get('Content-Disposition');
        let filename = 'document';
        if (contentDisposition) {
            const match = contentDisposition.match(/filename="(.+)"/);
            if (match) filename = match[1];
        }
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    } catch (error) {
        console.error('Error downloading document:', error);
        ui.showToast('Error downloading document', 'error');
    }
};

// Notes
function openNoteModal() {
    document.getElementById('note-form').reset();
    document.getElementById('note-modal').classList.remove('hidden');
}

function closeNoteModal() {
    document.getElementById('note-modal').classList.add('hidden');
}

async function saveNote() {
    const form = document.getElementById('note-form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    const noteData = {
        title: formData.get('title'),
        content: formData.get('content'),
        note_type: formData.get('note_type'),
        is_private: formData.get('is_private') === 'on',
    };

    if (!state.currentAnimatorId) {
        state.tempNotes.push(noteData);
        closeNoteModal();
        ui.showToast('Note will be saved with the new animator.');
        return;
    }

    try {
        await apiService.createNote(state.currentAnimatorId, noteData);
        closeNoteModal();
        loadNotes(state.currentAnimatorId);
        ui.showToast('Note added successfully!');
    } catch (error) {
        console.error('Error saving note:', error);
        ui.showToast('Error saving note', 'error');
    }
}

async function loadNotes(animatorId) {
    try {
        const data = await apiService.getNotes(animatorId);
        ui.renderNotesList(data.notes, animatorId);
    } catch (error) {
        console.error('Error loading notes:', error);
        ui.showToast('Error loading notes', 'error');
    }
}

window.deleteNote = async (animatorId, noteId) => {
    if (!confirm('Are you sure you want to delete this note?')) return;
    try {
        await apiService.deleteNote(animatorId, noteId);
        loadNotes(animatorId);
        ui.showToast('Note deleted successfully!');
    } catch (error) {
        console.error('Error deleting note:', error);
        ui.showToast('Error deleting note', 'error');
    }
};

window.editNote = (animatorId, noteId) => {
    // For simplicity, we'll just show an alert. In a real app, you'd open a modal
    alert('Edit note functionality would open a modal here');
};
