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

    // Children
    getChildren: (filters = {}) => fetchFromApi(`${window.location.origin}/api/children?${new URLSearchParams(filters)}`),
    getChild: (id) => fetchFromApi(`${window.location.origin}/api/children/${id}`),
    createChild: (data) => fetchFromApi(`${window.location.origin}/api/children`, { method: 'POST', body: JSON.stringify(data) }),
    updateChild: (id, data) => fetchFromApi(`${window.location.origin}/api/children/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    deleteChild: (id) => fetchFromApi(`${window.location.origin}/api/children/${id}`, { method: 'DELETE' }),
    getChildGuardians: (id) => fetchFromApi(`${window.location.origin}/api/children/${id}/guardians`),
    addChildGuardian: (id, data) => fetchFromApi(`${window.location.origin}/api/children/${id}/guardians`, { method: 'POST', body: JSON.stringify(data) }),

    // Calendar
    getEvents: (filters = {}) => fetchFromApi(`${window.location.origin}/api/calendar?${new URLSearchParams(filters)}`),
    getEvent: (id) => fetchFromApi(`${window.location.origin}/api/calendar/${id}`),
    createEvent: (data) => fetchFromApi(`${window.location.origin}/api/calendar`, { method: 'POST', body: JSON.stringify(data) }),
    updateEvent: (id, data) => fetchFromApi(`${window.location.origin}/api/calendar/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    deleteEvent: (id) => fetchFromApi(`${window.location.origin}/api/calendar/${id}`, { method: 'DELETE' }),
    getEventParticipants: (id) => fetchFromApi(`${window.location.origin}/api/calendar/${id}/participants`),

    // Attendance
    getAttendance: (filters = {}) => fetchFromApi(`${window.location.origin}/api/attendance?${new URLSearchParams(filters)}`),
    checkIn: (data) => fetchFromApi(`${window.location.origin}/api/attendance/checkin`, { method: 'POST', body: JSON.stringify(data) }),
    checkOut: (id, data = {}) => fetchFromApi(`${window.location.origin}/api/attendance/${id}/checkout`, { method: 'POST', body: JSON.stringify(data) }),
    deleteAttendance: (id) => fetchFromApi(`${window.location.origin}/api/attendance/${id}`, { method: 'DELETE' }),

    // Communications
    getCommunications: (filters = {}) => fetchFromApi(`${window.location.origin}/api/communications?${new URLSearchParams(filters)}`),
    getPublicCommunications: () => fetchFromApi(`${window.location.origin}/api/communications?${new URLSearchParams({ public: 1 })}`),
    getCommunication: (id) => fetchFromApi(`${window.location.origin}/api/communications/${id}`),
    createCommunication: (data) => fetchFromApi(`${window.location.origin}/api/communications`, { method: 'POST', body: JSON.stringify(data) }),
    updateCommunication: (id, data) => fetchFromApi(`${window.location.origin}/api/communications/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    deleteCommunication: (id) => fetchFromApi(`${window.location.origin}/api/communications/${id}`, { method: 'DELETE' }),
    getCommunicationComments: (id) => fetchFromApi(`${window.location.origin}/api/communications/${id}/comments`),
    addCommunicationComment: (id, data) => fetchFromApi(`${window.location.origin}/api/communications/${id}/comments`, { method: 'POST', body: JSON.stringify(data) }),

    // Media
    getMedia: (filters = {}) => fetchFromApi(`${window.location.origin}/api/media?${new URLSearchParams(filters)}`),
    getFolder: (id) => fetchFromApi(`${window.location.origin}/api/media/folders/${id}`),
    createFolder: (data) => fetchFromApi(`${window.location.origin}/api/media/folders`, { method: 'POST', body: JSON.stringify(data) }),
    uploadFile: (folderId, formData) => fetchFromApi(`${window.location.origin}/api/media`, { method: 'POST', body: formData }, false),
    downloadFile: (id) => fetchFromApi(`${window.location.origin}/api/media/files/${id}`, { headers: { 'Accept': '*/*' } }, false),
    deleteFile: (id) => fetchFromApi(`${window.location.origin}/api/media/files/${id}`, { method: 'DELETE' }),
    shareFile: (id) => fetchFromApi(`${window.location.origin}/api/media/share`, { method: 'POST', body: JSON.stringify({ file_id: id }) }),
    getShared: (token) => fetch(`${window.location.origin}/api/media/shared/${token}`).then(r => r.json()),

    // Wiki
    getWikiPages: (filters = {}) => fetchFromApi(`${window.location.origin}/api/wiki/pages?${new URLSearchParams(filters)}`),
    getWikiPage: (id) => fetchFromApi(`${window.location.origin}/api/wiki/pages/${id}`),
    createWikiPage: (data) => fetchFromApi(`${window.location.origin}/api/wiki/pages`, { method: 'POST', body: JSON.stringify(data) }),
    updateWikiPage: (id, data) => fetchFromApi(`${window.location.origin}/api/wiki/pages/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    deleteWikiPage: (id) => fetchFromApi(`${window.location.origin}/api/wiki/pages/${id}`, { method: 'DELETE' }),
    searchWiki: (query) => fetchFromApi(`${window.location.origin}/api/wiki/search?${new URLSearchParams({ q: query })}`),

    // Reports
    getReports: () => fetchFromApi(`${window.location.origin}/api/reports`),
    getAttendanceReport: (filters = {}) => fetchFromApi(`${window.location.origin}/api/reports/attendance?${new URLSearchParams(filters)}`),
    getChildrenReport: () => fetchFromApi(`${window.location.origin}/api/reports/children`),
    getAnimatorsReport: () => fetchFromApi(`${window.location.origin}/api/reports/animators`),
    getReportSummary: () => fetchFromApi(`${window.location.origin}/api/reports/summary`),

    // Spaces
    getSpaces: () => fetchFromApi(`${window.location.origin}/api/spaces`),
    getSpace: (id) => fetchFromApi(`${window.location.origin}/api/spaces/${id}`),
    createSpace: (data) => fetchFromApi(`${window.location.origin}/api/spaces`, { method: 'POST', body: JSON.stringify(data) }),
    updateSpace: (id, data) => fetchFromApi(`${window.location.origin}/api/spaces/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    deleteSpace: (id) => fetchFromApi(`${window.location.origin}/api/spaces/${id}`, { method: 'DELETE' }),
    getSpaceBookings: (id) => fetchFromApi(`${window.location.origin}/api/spaces/${id}/bookings`),
    createBooking: (data) => fetchFromApi(`${window.location.origin}/api/spaces/bookings`, { method: 'POST', body: JSON.stringify(data) }),
};
