// /public/js/ui.js

import { apiService } from './apiService.js';

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return `${date.toLocaleDateString()} ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
}

function calculateAge(birthDate) {
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }

    return age;
}

function getStatusColor(status) {
    const colors = {
        'active': 'bg-green-100 text-green-800',
        'inactive': 'bg-gray-100 text-gray-800',
        'suspended': 'bg-yellow-100 text-yellow-800',
        'terminated': 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
}


// UI components
function createAnimatorListItem(animator) {
    const age = animator.birth_date ? calculateAge(animator.birth_date) : 'N/A';
    const statusColor = getStatusColor(animator.status);

    return `
        <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
                                <i class="fas fa-user-tie text-purple-600 dark:text-purple-300 text-lg"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">${escapeHtml(animator.first_name)} ${escapeHtml(animator.last_name)}</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Animator #: ${animator.animator_number || 'N/A'}</p>
                            <div class="flex items-center space-x-4 mt-1">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-birthday-cake mr-1"></i>
                                    Age: ${age}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-graduation-cap mr-1"></i>
                                    ${escapeHtml(animator.specialization) || 'No specialization'}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusColor}">
                                    ${escapeHtml(animator.status)}
                                </span>
                            </div>
                            <div class="flex items-center space-x-2 mt-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-users mr-1"></i>
                                    ${animator.linked_users_count || 0} linked users
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-file-alt mr-1"></i>
                                    ${animator.documents_count || 0} documents
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-sticky-note mr-1"></i>
                                    ${animator.notes_count || 0} notes
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="viewAnimator(${animator.id})" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 p-1" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="editAnimator(${animator.id})" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 p-1" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteAnimator(${animator.id})" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 p-1" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

function createLinkedUserListItem(user) {
    return `
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
            <div class="flex justify-between items-center">
                <div>
                    <h5 class="font-medium text-gray-900 dark:text-gray-100">${escapeHtml(user.username)}</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">${escapeHtml(user.email)}</p>
                    <div class="flex items-center space-x-2 mt-1">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                            ${escapeHtml(user.relationship_type)}
                        </span>
                        ${user.notes ? `<span class="text-xs text-gray-500 dark:text-gray-400">${escapeHtml(user.notes)}</span>` : ''}
                    </div>
                </div>
                <button onclick="unlinkUser(${user.id})" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 p-2" title="Unlink User">
                    <i class="fas fa-unlink"></i>
                </button>
            </div>
        </div>
    `;
}

function createDocumentListItem(doc, animatorId) {
    return `
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-file text-blue-500 dark:text-blue-400 text-xl"></i>
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100">${escapeHtml(doc.name)}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">${escapeHtml(doc.type) || 'Document'} • ${formatFileSize(doc.size)}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Uploaded: ${formatDate(doc.created_at)}</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="downloadDocument(${doc.id}, ${animatorId})" class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 p-2" title="Download">
                        <i class="fas fa-download"></i>
                    </button>
                    <button onclick="deleteDocument(${animatorId}, ${doc.id})" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 p-2" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
}

function createNoteListItem(note, animatorId) {
    return `
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
            <div class="flex justify-between items-start mb-2">
                <h4 class="font-medium text-gray-900 dark:text-gray-100">${escapeHtml(note.title)}</h4>
                <div class="flex items-center space-x-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400">${formatDate(note.created_at)}</span>
                    <button onclick="editNote(${animatorId}, ${note.id})" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 p-1" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="deleteNote(${animatorId}, ${note.id})" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 p-1" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <p class="text-sm text-gray-700 dark:text-gray-300">${escapeHtml(note.content)}</p>
            <div class="mt-2 flex items-center space-x-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">By: ${escapeHtml(note.created_by_name)}</span>
                ${note.is_private ? '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">Private</span>' : ''}
            </div>
        </div>
    `;
}


// UI update functions
function renderAnimatorsList(animators) {
    const animatorsList = document.getElementById('animators-list');
    if (animators.length === 0) {
        animatorsList.innerHTML = '<div class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No animators found</div>';
        return;
    }
    animatorsList.innerHTML = animators.map(createAnimatorListItem).join('');
}

function renderPagination(paginationData) {
    document.getElementById('showing-from').textContent = ((paginationData.page - 1) * paginationData.limit) + 1;
    document.getElementById('showing-to').textContent = Math.min(paginationData.page * paginationData.limit, paginationData.total);
    document.getElementById('total-animators').textContent = paginationData.total;

    document.getElementById('prev-page').disabled = paginationData.page <= 1;
    document.getElementById('next-page').disabled = paginationData.page >= paginationData.pages;
}

function renderLinkedUsersList(users) {
    const linkedUsersList = document.getElementById('linked-users-list');
    if (users.length === 0) {
        linkedUsersList.innerHTML = '<div class="text-center text-gray-500 dark:text-gray-400">No users linked to this animator</div>';
        return;
    }
    linkedUsersList.innerHTML = users.map(createLinkedUserListItem).join('');
}

function renderDocumentsList(documents, animatorId) {
    const documentsList = document.getElementById('documents-list');
    if (documents.length === 0) {
        documentsList.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center py-4">No documents uploaded</p>';
        return;
    }
    documentsList.innerHTML = documents.map(doc => createDocumentListItem(doc, animatorId)).join('');
}

function renderNotesList(notes, animatorId) {
    const notesList = document.getElementById('notes-list');
    if (notes.length === 0) {
        notesList.innerHTML = '<p class="text-gray-500 dark:text-gray-400 text-center py-4">No notes available</p>';
        return;
    }
    notesList.innerHTML = notes.map(note => createNoteListItem(note, animatorId)).join('');
}

function showLoadingScreen() {
    document.getElementById('loading-screen').classList.remove('hidden');
}

function hideLoadingScreen() {
    document.getElementById('loading-screen').classList.add('hidden');
}

function showMainContent() {
    document.getElementById('main-content').classList.remove('hidden');
}

function showErrorScreen() {
    hideLoadingScreen();
    document.getElementById('main-content').classList.add('hidden');
    document.getElementById('error-screen').classList.remove('hidden');
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-5 right-5 p-4 rounded-lg shadow-lg text-white ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

export const ui = {
    renderAnimatorsList,
    renderPagination,
    renderLinkedUsersList,
    renderDocumentsList,
    renderNotesList,
    showLoadingScreen,
    hideLoadingScreen,
    showMainContent,
    showErrorScreen,
    showToast,
    escapeHtml,
};
