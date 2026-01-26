/**
 * Utility helper functions
 */

/**
 * Concatenate class names, filtering falsy values
 * @param  {...string} classes - Class names to concatenate
 * @returns {string} - Concatenated class names
 */
export function cn(...classes) {
    return classes.filter(Boolean).join(' ');
}

/**
 * Format a date string or Date object
 * @param {string|Date} date - Date to format
 * @param {object} options - Intl.DateTimeFormat options
 * @returns {string} - Formatted date string
 */
export function formatDate(date, options = {}) {
    if (!date) return '';

    const defaultOptions = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    };

    try {
        const d = typeof date === 'string' ? new Date(date) : date;
        return new Intl.DateTimeFormat('en-US', { ...defaultOptions, ...options }).format(d);
    } catch {
        return '';
    }
}

/**
 * Format a date for input fields (YYYY-MM-DD)
 * @param {string|Date} date - Date to format
 * @returns {string} - ISO date string
 */
export function formatDateForInput(date) {
    if (!date) return '';

    try {
        const d = typeof date === 'string' ? new Date(date) : date;
        return d.toISOString().split('T')[0];
    } catch {
        return '';
    }
}

/**
 * Debounce a function
 * @param {Function} fn - Function to debounce
 * @param {number} delay - Delay in milliseconds
 * @returns {Function} - Debounced function
 */
export function debounce(fn, delay = 300) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(this, args), delay);
    };
}

/**
 * Truncate text to a maximum length
 * @param {string} text - Text to truncate
 * @param {number} maxLength - Maximum length
 * @returns {string} - Truncated text
 */
export function truncate(text, maxLength = 100) {
    if (!text || text.length <= maxLength) return text || '';
    return text.substring(0, maxLength) + '...';
}

/**
 * Generate a unique ID
 * @param {string} prefix - Optional prefix
 * @returns {string} - Unique ID
 */
export function generateId(prefix = 'id') {
    return `${prefix}_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`;
}

/**
 * Check if a value is empty (null, undefined, empty string, empty array, empty object)
 * @param {*} value - Value to check
 * @returns {boolean} - True if empty
 */
export function isEmpty(value) {
    if (value === null || value === undefined) return true;
    if (typeof value === 'string') return value.trim() === '';
    if (Array.isArray(value)) return value.length === 0;
    if (typeof value === 'object') return Object.keys(value).length === 0;
    return false;
}

/**
 * Deep clone an object
 * @param {*} obj - Object to clone
 * @returns {*} - Cloned object
 */
export function deepClone(obj) {
    if (obj === null || typeof obj !== 'object') return obj;
    return JSON.parse(JSON.stringify(obj));
}

/**
 * Format file size in human readable format
 * @param {number} bytes - Size in bytes
 * @returns {string} - Formatted size
 */
export function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
