// API Configuration for Frontend
// This file provides the base API URL for all frontend JavaScript files

// Build the API base dynamically from the current origin so the app
// works whether Apache is on port 8080 or 8081 (or another port).
(function () {
    if (typeof window === 'undefined') return;

    const origin = window.location.origin || (window.location.protocol + '//' + window.location.hostname + (window.location.port ? ':' + window.location.port : ''));
    const basePath = '/project/api/api';
    const API_BASE_URL = origin + basePath;

    // Make it globally available
    window.API_BASE_URL = API_BASE_URL;
})();

