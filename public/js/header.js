document.addEventListener('DOMContentLoaded', function() {
    const headerPlaceholder = document.getElementById('main-header');
    if (headerPlaceholder) {
        fetch('../components/header.html')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(data => {
                headerPlaceholder.innerHTML = data;

                const logoutBtn = document.getElementById('logout-btn');
                if (logoutBtn) {
                    logoutBtn.addEventListener('click', () => {
                        const userToken = localStorage.getItem('animaid_token');

                        // We don't really care about the response of the logout endpoint
                        // as we are redirecting the user anyway.
                        fetch('/api/auth/logout', {
                            method: 'POST',
                            headers: { 'Authorization': `Bearer ${userToken}` }
                        }).finally(() => {
                            localStorage.removeItem('animaid_token');
                            localStorage.removeItem('animaid_user');
                            window.location.href = '../login.html';
                        });
                    });
                }

                // Dispatch event to signal header is loaded
                document.dispatchEvent(new CustomEvent('headerLoaded'));
            })
            .catch(error => {
                console.error('Error loading header:', error);
                headerPlaceholder.innerHTML = '<p class="text-red-500 text-center">Error loading navigation bar.</p>';
            });
    }
});
