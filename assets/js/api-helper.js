// API Helper Functions
// Provides common functions for making API calls to the backend

// Build API base URL dynamically from current location
// This works whether Apache is on port 80, 8080, 8081, or another port
(function() {
    if (typeof window !== 'undefined' && window.location) {
        // Helper function to get origin with port
        function getOriginWithPort() {
            if (window.location.origin) {
                return window.location.origin; // This includes protocol, hostname, and port
            }
            // Fallback: build origin manually
            const protocol = window.location.protocol || 'http:';
            const hostname = window.location.hostname || 'localhost';
            const port = window.location.port || '';
            // If no port specified and protocol is http, try common ports
            if (!port && protocol === 'http:') {
                // Default to 8080 if no port is specified (common XAMPP setup)
                return protocol + '//' + hostname + ':8080';
            }
            return protocol + '//' + hostname + (port ? ':' + port : '');
        }
        
        const origin = getOriginWithPort();
        
        // Extract the base path from current location
        // Use /api/api for the new API structure, fallback to /php for legacy endpoints
        const currentPath = window.location.pathname;
        let basePath = '/project/api/api'; // Use new API structure by default
        
        // Try to extract the project base path
        // Match patterns like: /project (5)/project/public/... or /project/public/...
        const pathParts = currentPath.split('/').filter(p => p);
        
        // Look for 'public' in the path - if found, go up one level to get to project root
        const publicIndex = pathParts.findIndex(p => p.toLowerCase() === 'public');
        if (publicIndex !== -1) {
            // Build path up to (but not including) 'public', then add 'api/api'
            const baseParts = pathParts.slice(0, publicIndex);
            basePath = '/' + baseParts.join('/') + '/api/api';
        } else {
            // Fallback: look for 'project' in the path
            const projectIndex = pathParts.findIndex(p => p.toLowerCase().includes('project'));
            if (projectIndex !== -1) {
                // Build path up to and including the first 'project' directory
                const baseParts = pathParts.slice(0, projectIndex + 1);
                basePath = '/' + baseParts.join('/') + '/api/api';
            }
        }
        
        // Only override if api-config.js hasn't set it yet, or if it's set to the wrong path
        if (!window.API_BASE_URL || window.API_BASE_URL.includes('/sofe-project/') || window.API_BASE_URL.includes('/php')) {
            window.API_BASE_URL = origin + basePath;
        }
        
        console.log('API_BASE_URL set to:', window.API_BASE_URL);
        console.log('Detected origin:', origin, 'Port:', window.location.port || 'default (8080)');
        console.log('Current path:', currentPath);
        console.log('Detected base path:', basePath);
    }
})();

// Getter function to always get the latest dynamically detected API_BASE_URL
// This ensures we use the correct URL even if api-config.js set it incorrectly
function getAPIBaseURL() {
    if (typeof window !== 'undefined' && window.API_BASE_URL) {
        // Double-check: if it's the old wrong path, recalculate
        if (window.API_BASE_URL.includes('/sofe-project/') || window.API_BASE_URL.includes('/php')) {
            // Recalculate the correct path to use /api/api
            const origin = window.location.origin || 
                (window.location.protocol + '//' + window.location.hostname + 
                 (window.location.port ? ':' + window.location.port : ''));
            const currentPath = window.location.pathname;
            const pathParts = currentPath.split('/').filter(p => p);
            const publicIndex = pathParts.findIndex(p => p.toLowerCase() === 'public');
            let basePath = '/project/api/api';
            if (publicIndex !== -1) {
                const baseParts = pathParts.slice(0, publicIndex);
                basePath = '/' + baseParts.join('/') + '/api/api';
            }
            window.API_BASE_URL = origin + basePath;
            console.log('🔧 Corrected API_BASE_URL to:', window.API_BASE_URL);
        }
        return window.API_BASE_URL;
    }
    // Fallback only if window is not available
    const origin = window.location ? (window.location.origin || 'http://localhost:8080') : 'http://localhost:8080';
    return origin + '/project/api/api';
}

// For backwards compatibility, but apiCall will use getAPIBaseURL() instead
const API_BASE_URL = getAPIBaseURL();

// Helper function to get auth token
function getAuthToken() {
    // Check both sessionStorage and localStorage for token
    const token = sessionStorage.getItem('token') || localStorage.getItem('token') || '';
    // Trim whitespace to prevent authentication issues
    return token ? token.trim() : '';
}

// Helper function to get auth headers
function getAuthHeaders() {
    return {
        'Content-Type': 'application/json',
        'Authorization': getAuthToken()
    };
}

// Generic API call function
async function apiCall(endpoint, method = 'GET', data = null, requireAuth = false) {
    // ALWAYS recalculate the API base URL to ensure we have the correct path
    // This prevents issues with cached values or incorrect URLs
    let baseUrl = getAPIBaseURL();
    
    // Force recalculation if we detect the old wrong URL
    if (baseUrl.includes('/sofe-project/') || baseUrl.includes('localhost/sofe-project') || baseUrl.includes('/php')) {
        console.warn('⚠️ Detected old wrong URL, forcing recalculation...');
        // Force recalculation to use /api/api
        const origin = window.location.origin || 
            (window.location.protocol + '//' + window.location.hostname + 
             (window.location.port ? ':' + window.location.port : ''));
        const currentPath = window.location.pathname;
        const pathParts = currentPath.split('/').filter(p => p);
        const publicIndex = pathParts.findIndex(p => p.toLowerCase() === 'public');
        let basePath = '/project/api/api';
        if (publicIndex !== -1) {
            const baseParts = pathParts.slice(0, publicIndex);
            basePath = '/' + baseParts.join('/') + '/api/api';
        }
        baseUrl = origin + basePath;
        window.API_BASE_URL = baseUrl;
        console.log('✅ Force-corrected to:', baseUrl);
    }
    
    const url = baseUrl + endpoint;
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };
    
    // Add auth token if available
    const token = getAuthToken();
    if (token) {
        options.headers['Authorization'] = token;
        console.log('🔑 Sending auth token:', token.substring(0, 20) + '...');
    } else {
        console.warn('⚠️ No auth token found for authenticated request');
    }
    
    if (data && (method === 'POST' || method === 'PUT')) {
        // Include token in request body as fallback if Authorization header might not work
        if (token && requireAuth) {
            data._token = token;
        }
        options.body = JSON.stringify(data);
    }
    
        console.log('🌐 API Call:', method, url);
        console.log('📍 Using API_BASE_URL:', baseUrl);
        if (data) {
            console.log('📦 API Request Data:', data);
        }
    
    try {
        const response = await fetch(url, options);
        
        // Check if response is actually JSON
        const contentType = response.headers.get('content-type');
        let result;
        
        if (contentType && contentType.includes('application/json')) {
            result = await response.json();
        } else {
            // If not JSON, get text for debugging
            const text = await response.text();
            console.error('Non-JSON response:', text);
            throw new Error(`Server returned non-JSON response: ${response.status} ${response.statusText}`);
        }
        
        if (!response.ok) {
            const errorMsg = result.message || `HTTP ${response.status}: ${response.statusText}`;
            console.error('API Error Response:', { 
                status: response.status, 
                statusText: response.statusText, 
                url: url,
                result 
            });
            throw new Error(errorMsg);
        }
        
        // Log response for Google OAuth debugging
        if (endpoint.includes('google-auth')) {
            console.log('✅ Google Auth Response:', result);
            if (result.success && result.data) {
                if (result.data.redirect_uri) {
                    console.log('🔗 Redirect URI:', result.data.redirect_uri);
                    console.log('📝 Add this exact URI to Google Cloud Console:');
                    console.log('   1. Go to: https://console.cloud.google.com/apis/credentials');
                    console.log('   2. Click your OAuth 2.0 Client ID');
                    console.log('   3. Under "Authorized redirect URIs", add:', result.data.redirect_uri);
                }
                if (result.data.auth_url) {
                    console.log('🔗 Auth URL generated successfully');
                }
            }
        }
        
        return result;
    } catch (error) {
        console.error('API Error Details:', {
            error: error,
            url: url,
            method: method,
            endpoint: endpoint,
            API_BASE_URL: baseUrl,
            window_API_BASE_URL: typeof window !== 'undefined' ? window.API_BASE_URL : 'N/A'
        });
        
        // If it's a network error, provide more helpful message
        if (error.message && error.message.includes('Failed to fetch')) {
            throw new Error(`Failed to connect to API at ${url}. Please check:\n1. Apache is running on port ${window.location.port || '8080'}\n2. The API endpoint exists\n3. CORS is enabled on the server`);
        }
        
        // If it's already an Error with a message, re-throw it
        if (error instanceof Error) {
            throw error;
        }
        // Otherwise wrap it
        throw new Error(error.message || 'Network error: Failed to connect to server');
    }
}

// Authentication APIs
const AuthAPI = {
    login: async (usernameOrEmail, password, role = null) => {
        // Support both username and email - API expects 'email' field but accepts either email or username value
        const loginData = {
            email: usernameOrEmail, // API expects 'email' field name but will match against both email and username columns
            password: password
        };
        if (role) loginData.role = role;
        return apiCall('/auth/login.php', 'POST', loginData);
    },
    
    register: async (userData) => {
        return apiCall('/auth/register.php', 'POST', userData);
    },
    
    logout: async () => {
        return apiCall('/auth/logout.php', 'POST');
    },
    
    googleAuth: async () => {
        const result = await apiCall('/auth/google-auth.php', 'GET');
        if (result.success && result.data.auth_url) {
            // Log redirect URI for debugging
            if (result.data.redirect_uri) {
                console.log('🔗 Google OAuth Redirect URI:', result.data.redirect_uri);
                console.log('📝 Add this exact URI to Google Cloud Console:');
                console.log('   1. Go to: https://console.cloud.google.com/apis/credentials');
                console.log('   2. Click your OAuth 2.0 Client ID');
                console.log('   3. Under "Authorized redirect URIs", add:', result.data.redirect_uri);
            }
            window.location.href = result.data.auth_url;
        } else {
            throw new Error(result.message || 'Failed to generate Google auth URL');
        }
        return result; // Return result for debugging
    }
};

// Products APIs
const ProductsAPI = {
    getAll: async () => {
        return apiCall('/products/get-all.php', 'GET');
    },
    
    get: async (id) => {
        return apiCall('/products/get.php?id=' + id, 'GET');
    },
    
    create: async (productData) => {
        return apiCall('/products/create.php', 'POST', productData, true);
    },
    
    update: async (id, productData) => {
        return apiCall('/products/update.php', 'PUT', { id, ...productData }, true);
    },
    
    delete: async (id) => {
        return apiCall('/products/delete.php?id=' + id, 'DELETE', null, true);
    }
};

// Orders APIs
const OrdersAPI = {
    create: async (orderData) => {
        return apiCall('/orders/create.php', 'POST', orderData);
    },
    
    getAll: async (filters = {}) => {
        const queryParams = new URLSearchParams(filters).toString();
        return apiCall('/orders/get-all.php' + (queryParams ? '?' + queryParams : ''), 'GET');
    },
    
    get: async (id, useOrderId = false) => {
        // Support both numeric ID and order_id string (e.g., ORD-001)
        const param = useOrderId ? 'order_id' : 'id';
        return apiCall('/orders/get.php?' + param + '=' + encodeURIComponent(id), 'GET');
    },
    
    updateStatus: async (id, orderStatus, paymentStatus = null) => {
        // Support both numeric ID and order_id string (e.g., ORD-20240101-0001)
        const isNumeric = /^\d+$/.test(String(id));
        const data = {
            order_status: orderStatus,
            payment_status: paymentStatus
        };
        
        if (isNumeric) {
            data.id = id;
        } else {
            data.order_id = id; // Use order_id for string IDs
        }
        
        return apiCall('/orders/update-status.php', 'POST', data, true);
    }
};

// Payments APIs
const PaymentsAPI = {
    createGCash: async (orderId, amount) => {
        // Using PayMongo for GCash payments
        return apiCall('/payments/paymongo-create.php', 'POST', {
            order_id: orderId,
            amount: amount
        });
    },
    createPayMongo: async (orderId, amount) => {
        return apiCall('/payments/paymongo-create.php', 'POST', {
            order_id: orderId,
            amount: amount
        });
    }
};

// Users APIs
const UsersAPI = {
    getProfile: async (id = null) => {
        const endpoint = id ? '/users/profile.php?user_id=' + id : '/users/profile.php';
        return apiCall(endpoint, 'GET', null, true);
    },
    
    updateProfile: async (profileData) => {
        return apiCall('/users/update.php', 'PUT', profileData, true);
    },
    
    submitInquiry: async (inquiryData) => {
        return apiCall('/users/inquiries.php', 'POST', inquiryData);
    },
    
    getInquiries: async () => {
        return apiCall('/users/inquiries.php', 'GET', null, true);
    }
};

// Make APIs globally available
if (typeof window !== 'undefined') {
    window.AuthAPI = AuthAPI;
    window.ProductsAPI = ProductsAPI;
    window.OrdersAPI = OrdersAPI;
    window.PaymentsAPI = PaymentsAPI;
    window.UsersAPI = UsersAPI;
    // Don't overwrite window.API_BASE_URL - it's already set dynamically by the IIFE above
    // window.API_BASE_URL is set at the top of this file with the correct dynamic path
}

