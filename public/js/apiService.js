// /public/js/apiService.js

async function fetchFromApi(url, options = {}, parseJson = true) {
    const token = localStorage.getItem('animaid_token');
    const headers = {
        'Authorization': `Bearer ${token}`,
        ...options.headers,
    };

    if (parseJson) {
        headers['Content-Type'] = 'application/json';
    }

    // For FormData, let the browser set the Content-Type with the boundary
    if (options.body instanceof FormData) {
        delete headers['Content-Type'];
    }

    try {
        const response = await fetch(url, { ...options, headers });
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ error: 'Invalid JSON response' }));
            throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
        }
        if (parseJson) {
            return response.json();
        }
        return response;
    } catch (error) {
        console.error('API Fetch Error:', error);
        throw error;
    }
}

export const apiService = {
    // Auth
    getSelf: () => fetchFromApi(`${window.location.origin}/api/auth/me`),
    logout: () => fetchFromApi(`${window.location.origin}/api/auth/logout`, { method: 'POST' }),

    // Animators
    getAnimators: (params) => fetchFromApi(`${window.location.origin}/api/animators?${new URLSearchParams(params)}`),
    getAnimator: (id) => fetchFromApi(`${window.location.origin}/api/animators/${id}`),
    createAnimator: (data) => fetchFromApi(`${window.location.origin}/api/animators`, { method: 'POST', body: JSON.stringify(data) }),
    updateAnimator: (id, data) => fetchFromApi(`${window.location.origin}/api/animators/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    deleteAnimator: (id) => fetchFromApi(`${window.location.origin}/api/animators/${id}`, { method: 'DELETE' }),

    // Linked Users
    getLinkedUsers: (animatorId) => fetchFromApi(`${window.location.origin}/api/animators/${animatorId}/users`),
    linkUser: (animatorId, data) => fetchFromApi(`${window.location.origin}/api/animators/${animatorId}/users`, { method: 'POST', body: JSON.stringify(data) }),
    unlinkUser: (animatorId, userId) => fetchFromApi(`${window.location.origin}/api/animators/${animatorId}/users`, { method: 'DELETE', body: JSON.stringify({ user_id: userId }) }),

    // Documents
    getDocuments: (animatorId) => fetchFromApi(`${window.location.origin}/api/animators/${animatorId}/documents`),
    uploadDocument: (animatorId, formData) => fetchFromApi(`${window.location.origin}/api/animators/${animatorId}/documents`, { method: 'POST', body: formData }, false),
    deleteDocument: (animatorId, documentId) => fetchFromApi(`${window.location.origin}/api/animators/${animatorId}/documents/${documentId}`, { method: 'DELETE' }),
    downloadDocument: (animatorId, documentId) => fetchFromApi(`${window.location.origin}/api/animators/${animatorId}/documents/${documentId}`, { headers: { 'Accept': '*/*' } }, false),

    // Notes
    getNotes: (animatorId) => fetchFromApi(`${window.location.origin}/api/animators/${animatorId}/notes`),
    createNote: (animatorId, data) => fetchFromApi(`${window.location.origin}/api/animators/${animatorId}/notes`, { method: 'POST', body: JSON.stringify(data) }),
    deleteNote: (animatorId, noteId) => fetchFromApi(`${window.location.origin}/api/animators/${animatorId}/notes/${noteId}`, { method: 'DELETE' }),

    // Users
    getUsers: () => fetchFromApi(`${window.location.origin}/api/users?page=1&limit=100`),
};
