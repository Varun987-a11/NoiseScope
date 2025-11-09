// admin.js (Visualization, Table, and Dashboard Logic)

let map;
let noiseChart;

// --- Map Initialization & Visualization ---
function initializeMap() {
    map = L.map('map').setView([28.6139, 77.2090], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
}

function getMarkerColor(dB) {
    if (dB < 45) return '#28a745'; 
    if (dB < 60) return '#007bff';
    if (dB < 75) return '#ffc107';
    return '#dc3545';
}

function updateMap(data) {
    map.eachLayer(layer => {
        if (layer instanceof L.Marker && layer.options && layer.options.icon) {
            map.removeLayer(layer);
        }
    });
    
    data.forEach(point => {
        const color = getMarkerColor(point.avg_noise_level_db);
        const customIcon = L.divIcon({
            className: 'custom-div-icon',
            html: `<div style="background-color:${color}; width: 15px; height: 15px; border-radius: 50%; border: 2px solid white;"></div>`,
            iconSize: [20, 20],
            iconAnchor: [10, 10]
        });

        // Popup includes the new environment type and submitter
        L.marker([point.latitude, point.longitude], {icon: customIcon})
            .bindPopup(`
                <b>${point.location_name}</b><br>
                Noise Level: ${point.avg_noise_level_db} dB<br>
                Type: ${point.environment_type.charAt(0).toUpperCase() + point.environment_type.slice(1)}<br>
                Submitter: ${point.submitter}<br>
                Time: ${new Date(point.timestamp).toLocaleString()}
            `)
            .addTo(map);
    });
}

// --- Chart Visualization ---
function updateChart(data) {
    if (noiseChart) {
        noiseChart.destroy();
    }

    const chartData = data.slice(0, 20).reverse(); 
    const labels = chartData.map(d => new Date(d.timestamp).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }));
    const dbValues = chartData.map(d => d.avg_noise_level_db);

    const ctx = document.getElementById('noiseChart').getContext('2d');
    
    noiseChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Noise Level (dB)',
                data: dbValues,
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                borderWidth: 2,
                pointRadius: 5,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
        }
    });
}

// --- Table & User List Management ---

function updateDataTable(data) {
    const tbody = document.getElementById('dataTable').getElementsByTagName('tbody')[0];
    tbody.innerHTML = ''; // Clear existing rows

    data.forEach(point => {
        const row = tbody.insertRow();
        row.insertCell().textContent = point.id;
        row.insertCell().textContent = point.location_name;
        row.insertCell().textContent = point.avg_noise_level_db;
        row.insertCell().textContent = point.environment_type.charAt(0).toUpperCase() + point.environment_type.slice(1);
        row.insertCell().textContent = point.submitter;
        row.insertCell().textContent = new Date(point.timestamp).toLocaleString();
        
        // Action button placeholder (Requires new backend APIs for delete/edit)
        const actionCell = row.insertCell();
        actionCell.innerHTML = '<button onclick="alert(\'Action requires new API\')" style="padding: 3px 6px; font-size: 0.8em;">Manage</button>';
    });
}

// NOTE: This assumes a separate backend API to get registered users.
async function fetchUsers() {
    try {
        // Placeholder: Since we don't have a dedicated get_users API, we will simulate
        // or fetch from the admin data if we can parse submitters. 
        // For a proper solution, you'd create backend/get_users.php
        
        // Since we don't have get_users.php, we'll list the submitters from the data
        const dataResponse = await fetch('backend/get_admin_data.php');
        const data = await dataResponse.json();
        const submitters = [...new Set(data.map(d => d.submitter))]; // Get unique submitters

        const ul = document.getElementById('userList');
        ul.innerHTML = '';
        submitters.forEach(user => {
            const li = document.createElement('li');
            li.textContent = user;
            li.style.padding = '5px';
            li.style.borderBottom = '1px dotted #ccc';
            ul.appendChild(li);
        });

    } catch (error) {
        console.error('Failed to fetch user list:', error);
    }
}


// --- Main Data Loading Function ---
async function loadDataAndVisualize() {
    try {
        const response = await fetch('backend/get_admin_data.php');
        const data = await response.json();
        
        if (Array.isArray(data)) {
            updateMap(data);
            updateChart(data);
            updateDataTable(data);
            fetchUsers(data); // List users based on who submitted
        } else {
            console.error("Data retrieval error:", data);
        }

    } catch (error) {
        console.error('Failed to fetch data for dashboard:', error);
    }
}

// --- Initial Load ---
document.addEventListener('DOMContentLoaded', () => {
    initializeMap();
    loadDataAndVisualize();
    
    // Auto-refresh the dashboard data every 30 seconds
    setInterval(loadDataAndVisualize, 30000); 
});