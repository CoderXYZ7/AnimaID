/**
 * AnimaID Modal System
 * Custom modal dialogs to replace browser-native alert(), confirm(), and prompt()
 * Supports dark mode and matches site styling
 */

const Modal = (function () {
    // Modal container element
    let container = null;

    // Icon configurations
    const icons = {
        success: { icon: 'fa-check-circle', color: 'text-green-500' },
        error: { icon: 'fa-times-circle', color: 'text-red-500' },
        warning: { icon: 'fa-exclamation-triangle', color: 'text-yellow-500' },
        info: { icon: 'fa-info-circle', color: 'text-blue-500' },
        question: { icon: 'fa-question-circle', color: 'text-purple-500' }
    };

    /**
     * Initialize the modal container
     */
    function init() {
        if (container) return;

        container = document.createElement('div');
        container.id = 'modal-container';
        container.innerHTML = `
            <style>
                #modal-container {
                    position: fixed;
                    inset: 0;
                    z-index: 99999;
                    display: none;
                    align-items: center;
                    justify-content: center;
                    padding: 1rem;
                }
                #modal-backdrop {
                    position: absolute;
                    inset: 0;
                    background-color: rgba(0, 0, 0, 0.5);
                    backdrop-filter: blur(2px);
                }
                #modal-content {
                    position: relative;
                    background-color: white;
                    border-radius: 0.75rem;
                    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                    max-width: 28rem;
                    width: 100%;
                    transform: scale(0.95);
                    opacity: 0;
                    transition: transform 0.15s ease-out, opacity 0.15s ease-out;
                }
                #modal-container.show #modal-content {
                    transform: scale(1);
                    opacity: 1;
                }
                .dark #modal-content {
                    background-color: #1f2937;
                    color: #f9fafb;
                }
                #modal-icon {
                    font-size: 3rem;
                    margin-bottom: 1rem;
                }
                #modal-title {
                    font-size: 1.25rem;
                    font-weight: 600;
                    color: #111827;
                    margin-bottom: 0.5rem;
                }
                .dark #modal-title {
                    color: #f9fafb;
                }
                #modal-message {
                    color: #4b5563;
                    white-space: pre-wrap;
                    word-break: break-word;
                }
                .dark #modal-message {
                    color: #d1d5db;
                }
                #modal-input {
                    width: 100%;
                    margin-top: 1rem;
                    padding: 0.5rem 0.75rem;
                    border: 1px solid #d1d5db;
                    border-radius: 0.5rem;
                    font-size: 1rem;
                    outline: none;
                }
                #modal-input:focus {
                    border-color: #3b82f6;
                    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
                }
                .dark #modal-input {
                    background-color: #374151;
                    border-color: #4b5563;
                    color: #f9fafb;
                }
                .dark #modal-input:focus {
                    border-color: #60a5fa;
                }
                #modal-buttons {
                    display: flex;
                    gap: 0.75rem;
                    margin-top: 1.5rem;
                }
                #modal-buttons button {
                    flex: 1;
                    padding: 0.625rem 1rem;
                    border-radius: 0.5rem;
                    font-weight: 500;
                    font-size: 0.875rem;
                    cursor: pointer;
                    transition: all 0.15s ease;
                    border: none;
                }
                #modal-cancel-btn {
                    background-color: #f3f4f6;
                    color: #374151;
                }
                #modal-cancel-btn:hover {
                    background-color: #e5e7eb;
                }
                .dark #modal-cancel-btn {
                    background-color: #374151;
                    color: #d1d5db;
                }
                .dark #modal-cancel-btn:hover {
                    background-color: #4b5563;
                }
                #modal-confirm-btn {
                    background-color: #3b82f6;
                    color: white;
                }
                #modal-confirm-btn:hover {
                    background-color: #2563eb;
                }
                #modal-confirm-btn.dangerous {
                    background-color: #ef4444;
                }
                #modal-confirm-btn.dangerous:hover {
                    background-color: #dc2626;
                }
            </style>
            <div id="modal-backdrop"></div>
            <div id="modal-content">
                <div style="padding: 1.5rem; text-align: center;">
                    <div id="modal-icon"></div>
                    <h3 id="modal-title"></h3>
                    <div id="modal-message"></div>
                    <input type="text" id="modal-input" style="display: none;">
                    <div id="modal-buttons">
                        <button id="modal-cancel-btn" style="display: none;">Cancel</button>
                        <button id="modal-confirm-btn">OK</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(container);

        // Close on backdrop click
        container.querySelector('#modal-backdrop').addEventListener('click', () => {
            if (currentReject) currentReject(null);
            hide();
        });

        // Handle keyboard
        document.addEventListener('keydown', handleKeydown);
    }

    let currentResolve = null;
    let currentReject = null;
    let currentType = 'alert';

    function handleKeydown(e) {
        if (!container || container.style.display === 'none') return;

        if (e.key === 'Escape') {
            e.preventDefault();
            if (currentType === 'alert') {
                currentResolve && currentResolve();
            } else {
                currentResolve && currentResolve(currentType === 'confirm' ? false : null);
            }
            hide();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const input = container.querySelector('#modal-input');
            if (currentType === 'prompt') {
                currentResolve && currentResolve(input.value);
            } else if (currentType === 'confirm') {
                currentResolve && currentResolve(true);
            } else {
                currentResolve && currentResolve();
            }
            hide();
        }
    }

    function show(options) {
        init();

        const {
            type = 'alert',
            title = '',
            message = '',
            icon = null,
            confirmText = 'OK',
            cancelText = 'Cancel',
            dangerous = false,
            defaultValue = ''
        } = options;

        currentType = type;

        const iconEl = container.querySelector('#modal-icon');
        const titleEl = container.querySelector('#modal-title');
        const messageEl = container.querySelector('#modal-message');
        const inputEl = container.querySelector('#modal-input');
        const cancelBtn = container.querySelector('#modal-cancel-btn');
        const confirmBtn = container.querySelector('#modal-confirm-btn');

        // Set icon
        if (icon && icons[icon]) {
            iconEl.innerHTML = `<i class="fas ${icons[icon].icon} ${icons[icon].color}"></i>`;
            iconEl.style.display = 'block';
        } else {
            iconEl.style.display = 'none';
        }

        // Set title
        if (title) {
            titleEl.textContent = title;
            titleEl.style.display = 'block';
        } else {
            titleEl.style.display = 'none';
        }

        // Set message
        messageEl.textContent = message;

        // Set input (for prompt)
        if (type === 'prompt') {
            inputEl.style.display = 'block';
            inputEl.value = defaultValue;
        } else {
            inputEl.style.display = 'none';
        }

        // Set buttons
        if (type === 'confirm' || type === 'prompt') {
            cancelBtn.style.display = 'block';
            cancelBtn.textContent = cancelText;
        } else {
            cancelBtn.style.display = 'none';
        }

        confirmBtn.textContent = confirmText;
        confirmBtn.className = dangerous ? 'dangerous' : '';

        // Show modal
        container.style.display = 'flex';
        requestAnimationFrame(() => {
            container.classList.add('show');
        });

        // Focus appropriate element
        if (type === 'prompt') {
            inputEl.focus();
            inputEl.select();
        } else {
            confirmBtn.focus();
        }

        return new Promise((resolve, reject) => {
            currentResolve = resolve;
            currentReject = reject;

            cancelBtn.onclick = () => {
                resolve(type === 'confirm' ? false : null);
                hide();
            };

            confirmBtn.onclick = () => {
                if (type === 'prompt') {
                    resolve(inputEl.value);
                } else if (type === 'confirm') {
                    resolve(true);
                } else {
                    resolve();
                }
                hide();
            };
        });
    }

    function hide() {
        if (!container) return;
        container.classList.remove('show');
        setTimeout(() => {
            container.style.display = 'none';
        }, 150);
        currentResolve = null;
        currentReject = null;
    }

    /**
     * Show an alert modal
     * @param {string} message - Message to display
     * @param {Object} options - Options (title, icon, confirmText)
     * @returns {Promise<void>}
     */
    function alert(message, options = {}) {
        return show({
            type: 'alert',
            message,
            icon: options.icon || 'info',
            ...options
        });
    }

    /**
     * Show a success alert
     * @param {string} message - Message to display
     * @param {Object} options - Options
     * @returns {Promise<void>}
     */
    function success(message, options = {}) {
        return show({
            type: 'alert',
            message,
            icon: 'success',
            title: options.title || 'Success',
            ...options
        });
    }

    /**
     * Show an error alert
     * @param {string} message - Message to display
     * @param {Object} options - Options
     * @returns {Promise<void>}
     */
    function error(message, options = {}) {
        return show({
            type: 'alert',
            message,
            icon: 'error',
            title: options.title || 'Error',
            ...options
        });
    }

    /**
     * Show a warning alert
     * @param {string} message - Message to display
     * @param {Object} options - Options
     * @returns {Promise<void>}
     */
    function warning(message, options = {}) {
        return show({
            type: 'alert',
            message,
            icon: 'warning',
            title: options.title || 'Warning',
            ...options
        });
    }

    /**
     * Show a confirm modal
     * @param {string} message - Message to display
     * @param {Object} options - Options (title, icon, confirmText, cancelText, dangerous)
     * @returns {Promise<boolean>}
     */
    function confirm(message, options = {}) {
        return show({
            type: 'confirm',
            message,
            icon: options.icon || 'question',
            ...options
        });
    }

    /**
     * Show a prompt modal
     * @param {string} message - Message to display
     * @param {Object} options - Options (title, defaultValue, confirmText, cancelText)
     * @returns {Promise<string|null>}
     */
    function prompt(message, options = {}) {
        return show({
            type: 'prompt',
            message,
            icon: options.icon || 'question',
            ...options
        });
    }

    return {
        alert,
        success,
        error,
        warning,
        confirm,
        prompt
    };
})();

// Make Modal available globally
window.Modal = Modal;
