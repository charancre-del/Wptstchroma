/**
 * API Client for Chroma QA Reports
 * Handles WP Nonce, Session Expiry, and Concurrency Locking
 */

// Global config from wp_localize_script
const config = window.cqaData || {
    restUrl: '/wp-json/cqa/v1/',
    nonce: '',
};

class ApiError extends Error {
    constructor(message, status, code, data) {
        super(message);
        this.status = status;
        this.code = code;
        this.data = data;
    }
}

/**
 * Main Fetch Wrapper
 */
export const apiFetch = async (endpoint, options = {}) => {
    const isFormData = options.body instanceof FormData;

    // Default headers
    const headers = {
        'X-WP-Nonce': config.nonce,
        ...options.headers,
    };

    // AUDIT FINDING #1: Strict JSON Payload
    // Only set Content-Type for JSON requests.
    // FormData uploads must NOT have Content-Type set (browser sets boundary).
    if (!isFormData && options.body) {
        headers['Content-Type'] = 'application/json';
    }

    // AUDIT FINDING #3: Concurrency Locking
    // If 'ifUnmodifiedSince' option is passed, set the header
    if (options.ifUnmodifiedSince) {
        headers['If-Unmodified-Since'] = options.ifUnmodifiedSince;
    }

    // Build URL with query params support
    let url = `${config.restUrl.replace(/\/$/, '')}/${endpoint.replace(/^\//, '')}`;

    // Handle params option (like axios)
    if (options.params) {
        const searchParams = new URLSearchParams();
        Object.entries(options.params).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                searchParams.append(key, value);
            }
        });
        const queryString = searchParams.toString();
        if (queryString) {
            url += (url.includes('?') ? '&' : '?') + queryString;
        }
    }

    try {
        const response = await fetch(url, {
            ...options,
            headers,
            body: isFormData ? options.body : JSON.stringify(options.body),
        });

        // Parse JSON response
        let data;
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.indexOf('application/json') !== -1) {
            data = await response.json();
        } else {
            // Handle empty responses (like 204)
            data = {};
        }

        // Handle Errors based on Status
        if (!response.ok) {
            // 401: Session Expired
            if (response.status === 401) {
                // Trigger global session expired event (handled by AuthStore/UI)
                window.dispatchEvent(new CustomEvent('cqa:session-expired'));
                throw new ApiError('Session Expired', 401, 'session_expired');
            }

            // 409: Conflict (Optimistic Locking)
            if (response.status === 409) {
                // Return data so UI can show "Modified by [User]"
                throw new ApiError('Conflict', 409, 'conflict', data);
            }

            // Other errors
            const errorMessage = data.message || data.error?.message || 'An unexpected error occurred';
            const errorCode = data.code || data.error?.code || 'unknown_error';
            throw new ApiError(errorMessage, response.status, errorCode, data);
        }

        return data;
    } catch (error) {
        // If it's already an ApiError, rethrow it
        if (error instanceof ApiError) {
            throw error;
        }

        // Network errors
        throw new ApiError(error.message, 0, 'network_error');
    }
};

// Axios-like wrapper for compatibility with useQueries
export const apiClient = {
    get: (endpoint, config = {}) => apiFetch(endpoint, { ...config, method: 'GET' }),
    post: (endpoint, data, config = {}) => apiFetch(endpoint, { ...config, method: 'POST', body: data }),
    put: (endpoint, data, config = {}) => apiFetch(endpoint, { ...config, method: 'PUT', body: data }),
    delete: (endpoint, config = {}) => apiFetch(endpoint, { ...config, method: 'DELETE' }),
};

/**
 * Pulse check to keep nonce alive during long sessions
 */
let pulseInterval = null;
export const startNoncePulse = (intervalMs = 300000) => { // Default 5 mins
    if (pulseInterval) clearInterval(pulseInterval);

    pulseInterval = setInterval(async () => {
        try {
            // Ping /me - this updates the cookie/session and validates nonce
            await apiFetch('/me');
        } catch (e) {
            if (e.status === 401) {
                console.warn('[CQA] Pulse failed: Session expired');
                clearInterval(pulseInterval);
            }
        }
    }, intervalMs);
};

export default apiFetch;
