// --- Global Variables and Initialization ---
let map;
let adminMarkerLayer; // New layer for admin map markers

// --- Helper Functions ---

function initializeAdminMap(data) {
    const defaultLat = 28.6139;
    const defaultLng = 77.2090;
    
    map = L.map('map').setView([defaultLat, defaultLng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    adminMarkerLayer = L.layerGroup().addTo(map);
    updateAdminMap(data);
}

function getMarkerColor(dB) {
    if (dB < 40) return 'green';
    if (dB < 60) return 'blue';
    if (dB < 75) return 'orange';
    return 'red';
}

function updateAdminMap(data) {
    adminMarkerLayer.clearLayers();
    
    data.forEach(point => {
        const color = getMarkerColor(point.avg_noise_level_db);
        
        const customIcon = L.divIcon({
            className: 'custom-div-icon',
            html: `<div style="background-color:${color}; width: 15px; height: 15px; border-radius: 50%; border: 2px solid white;"></div>`,
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        });

        L.marker([point.latitude, point.longitude], {icon: customIcon})
            .bindPopup(`
                <b>${point.location_name}</b><br>
                Noise Level: ${point.avg_noise_level_db} dB<br>
                Submitted: ${new Date(point.timestamp).toLocaleString()}
            `)
            .addTo(adminMarkerLayer);
    });
}

function updateAdminTable(data) {
    const tbody = document.getElementById('adminDataTable').querySelector('tbody');
    tbody.innerHTML = ''; // Clear existing data
    
    data.forEach(point => {
        const row = tbody.insertRow();
        row.insertCell().textContent = point.id;
        row.insertCell().textContent = point.location_name;
        row.insertCell().textContent = `${point.latitude.toFixed(6)}, ${point.longitude.toFixed(6)}`;
        row.insertCell().textContent = point.avg_noise_level_db;
        row.insertCell().textContent = new Date(point.timestamp).toLocaleString();
    });
}

async function loadAdminData() {
    const authMessage = document.getElementById('authMessage');
    authMessage.textContent = 'Fetching data...';
    
    try {
        // Fetch all data from the public endpoint
        const response = await fetch('backend/get_data.php');
        const data = await response.json();
        
        if (Array.isArray(data)) {
            authMessage.textContent = `Total Submissions: ${data.length}`;
            updateAdminTable(data);
            updateAdminMap(data);
        } else {
            authMessage.textContent = 'Error: Could not retrieve data.';
        }
    } catch (error) {
        authMessage.textContent = 'Network error. Could not connect to the API.';
        console.error('Admin data load error:', error);
    }
}

async function checkAdminStatus() {
    try {
        const response = await fetch('backend/check_session.php');
        const data = await response.json();

        if (data.status === 'logged_in' && data.user_type === 'admin') {
            // Admin is logged in, load the data
            document.getElementById('authMessage').textContent = `Welcome back, ${data.username}! Loading full data set.`;
            loadAdminData();
        } else {
            // Not logged in or not an admin, redirect to login page
            alert("Access Denied: Only administrators can view this page.");
            window.location.href = 'index.html';
        }
    } catch (error) {
        console.error("Session check failed:", error);
        window.location.href = 'index.html';
    }
}

// --- Initial Load ---
document.addEventListener('DOMContentLoaded', () => {
    // Initialize map structure first
    initializeAdminMap([]); 
    // Then check authentication and load data
    checkAdminStatus();
});