<?php
// Start the session to access stored login variables
session_start();

// Check if the user is NOT logged in or is NOT marked as an admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== TRUE) {
    // If unauthorized, redirect them to the login page immediately
    header('Location: admin_login.html');
    exit();
}
// If they reach this point, they are logged in as an admin!
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NoiseScope</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f7f6; }
        .header { background-color: #28a745; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .header div { display: flex; align-items: center; }
        .header span { margin-right: 15px; }
        .header a { color: white; text-decoration: none; padding: 8px 15px; border: 1px solid white; border-radius: 4px; transition: background-color 0.3s; }
        .header a:hover { background-color: #1e7e34; }
        .content { padding: 20px; max-width: 1400px; margin: auto; }
        
        /* Tab Styles */
        .tabs { display: flex; margin-bottom: 20px; border-bottom: 2px solid #ccc; }
        .tab-button { background-color: #f1f1f1; border: none; padding: 10px 20px; cursor: pointer; transition: background-color 0.3s; margin-right: 5px; border-radius: 5px 5px 0 0; }
        .tab-button:hover { background-color: #ddd; }
        .tab-button.active { background-color: #fff; border-top: 2px solid #28a745; border-left: 1px solid #ccc; border-right: 1px solid #ccc; border-bottom: none; }
        .tab-content { background-color: white; padding: 20px; border-radius: 0 0 8px 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }

        /* Map and Table Styles */
        #map { height: 600px; width: 100%; border-radius: 8px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 14px; }
        th { background-color: #f2f2f2; }
        .delete-btn { background-color: #dc3545; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px; }
        .delete-btn:hover { background-color: #c82333; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 800px; border-radius: 8px; position: relative; }
        .close-btn { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
        .close-btn:hover, .close-btn:focus { color: #000; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>NoiseScope Admin Dashboard</h1>
        <div>
            <span>Welcome, **<?php echo htmlspecialchars($_SESSION['username']); ?>**!</span>
            <a href="backend/logout.php">Log Out</a>
        </div>
    </div>

    <div class="content">
        <div class="tabs">
            <button class="tab-button active" onclick="openTab(event, 'Map')">Map Overview</button>
            <button class="tab-button" onclick="openTab(event, 'Submissions')">Manage Submissions</button>
            <button class="tab-button" onclick="openTab(event, 'Users')">Registered Users</button>
        </div>

        <div class="tab-content">
            <div id="Map" class="tab-pane active">
                <h2>Live Noise Map</h2>
                <p>Click on any marker to view a graph of the historical submissions at that location.</p>
                <div id="map"></div>
            </div>

            <div id="Submissions" class="tab-pane">
                <h2>Submissions Database Table</h2>
                <p id="submissionsStatus">Loading data...</p>
                <table id="submissionsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Location Name</th>
                            <th>Lat, Lng</th>
                            <th>dB Level</th>
                            <th>Type</th>
                            <th>Timestamp</th>
                            <th>Submitter</th> <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>
            </div>

            <div id="Users" class="tab-pane">
                <h2>Registered Users</h2>
                <p id="usersStatus">Loading user list...</p>
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Admin Status</th>
                            <th>Registered On</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="graphModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h3 id="graphTitle">Noise Level History</h3>
            <canvas id="noiseChart"></canvas>
            <p style="margin-top: 15px;">*Note: This graph shows the time history of all collected noise averages at this specific location.</p>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
   <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // --- Dashboard Initialization and Global State ---
        let map;
        let chartInstance = null;
        // Fallback coordinates (New Delhi)
        const FALLBACK_CENTER = [28.6139, 77.2090]; 
        const FALLBACK_ZOOM = 13;
        let noiseDataCache = []; 
        let adminDataCache = []; 

        document.addEventListener('DOMContentLoaded', () => {
            const initialTabButton = document.querySelector('.tab-button');
            if (initialTabButton) {
                openTab({ currentTarget: initialTabButton }, 'Map'); 
            }
            
            // Start the process of getting location and initializing the map
            initializeMapFromCurrentLocation(); 
            loadAdminData();
        });

        // --- CORE CHANGE: Map Initialization Function ---
        function initializeMapFromCurrentLocation() {
            if (map) return;
            
            // 1. Attempt to get the current geolocation
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        // Success: Use current position
                        const currentLat = position.coords.latitude;
                        const currentLng = position.coords.longitude;
                        initializeMap([currentLat, currentLng], FALLBACK_ZOOM);
                    },
                    (error) => {
                        // Error/Denial: Use fallback center
                        console.warn("Geolocation failed or denied. Error:", error);
                        initializeMap(FALLBACK_CENTER, FALLBACK_ZOOM);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 0
                    }
                );
            } else {
                // Browser doesn't support Geolocation: Use fallback center
                console.warn("Geolocation is not supported by this browser.");
                initializeMap(FALLBACK_CENTER, FALLBACK_ZOOM);
            }
        }
        
        // New base function that handles the actual map setup and data loading
        function initializeMap(center, zoom) {
            map = L.map('map').setView(center, zoom); 

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            loadMapData(); // Load data once the map is ready
        }
        // ---------------------------------------------------------------------

        function openTab(evt, tabName) {
            const tabPanes = document.getElementsByClassName("tab-pane");
            for (let i = 0; i < tabPanes.length; i++) {
                tabPanes[i].classList.remove("active");
            }

            const tabButtons = document.getElementsByClassName("tab-button");
            for (let i = 0; i < tabButtons.length; i++) {
                tabButtons[i].classList.remove("active");
            }

            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
            
            if (tabName === 'Map') {
                setTimeout(() => { 
                    if (map) map.invalidateSize(true);
                }, 50); 
            } else if (tabName === 'Users') {
                fetchUserList();
            } else if (tabName === 'Submissions') {
                loadAdminSubmissions();
            }
        }

        // --- MAP FUNCTIONS (Data Loading and Rendering) ---
        function getMarkerColor(dB) {
            if (dB < 45) return '#4CAF50'; 
            if (dB < 60) return '#2196F3'; 
            if (dB < 75) return '#FFC107'; 
            return '#F44336'; 
        }

        function updateMap(data) {
            map.eachLayer(layer => {
                if (layer instanceof L.Marker) {
                    map.removeLayer(layer);
                }
            });
            
            data.forEach(point => {
                const color = getMarkerColor(point.avg_noise_level_db);
                
                const customIcon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div style="background-color:${color}; width: 15px; height: 15px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 5px ${color};"></div>`,
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                });

                const marker = L.marker([point.latitude, point.longitude], {icon: customIcon})
                    .bindPopup(`
                        <b style="color: ${color};">${point.location_name}</b><br>
                        Noise: ${point.avg_noise_level_db} dB<br>
                        Type: ${point.environment_type.charAt(0).toUpperCase() + point.environment_type.slice(1)}
                    `)
                    .addTo(map);

                marker.on('click', () => showGraphModal(point));
            });
        }

        async function loadMapData() {
            if (noiseDataCache.length > 0) { updateMap(noiseDataCache); return; }
            try {
                const response = await fetch('backend/get_data.php'); 
                const data = await response.json();
                if (Array.isArray(data)) { noiseDataCache = data; updateMap(data); }
            } catch (error) { console.error('Failed to fetch map data:', error); }
        }
        
        // --- ADMIN TABLE FUNCTIONS (No changes needed) ---
        async function loadAdminData() {
            await loadAdminSubmissions(); 
            fetchUserList();
        }

        async function loadAdminSubmissions() {
            if (adminDataCache.length > 0) { renderSubmissionsTable(adminDataCache); return; }
            const status = document.getElementById('submissionsStatus');
            status.textContent = 'Fetching detailed submissions...';
            try {
                const response = await fetch('backend/get_admin_data.php'); 
                const data = await response.json();
                if (Array.isArray(data)) { adminDataCache = data; renderSubmissionsTable(data); } 
                else { renderSubmissionsTable([]); }
            } catch (error) { 
                console.error('Failed to fetch admin data:', error);
                status.textContent = 'Failed to load detailed submissions (Check backend/get_admin_data.php).';
            }
        }
        
        function renderSubmissionsTable(data) {
            const submissionsTableBody = document.querySelector('#submissionsTable tbody');
            const status = document.getElementById('submissionsStatus');
            submissionsTableBody.innerHTML = '';
            
            if (data.length > 0) {
                data.forEach(point => {
                    const row = submissionsTableBody.insertRow();
                    row.insertCell().textContent = point.id;
                    row.insertCell().textContent = point.location_name;
                    row.insertCell().textContent = `${parseFloat(point.latitude).toFixed(4)}, ${parseFloat(point.longitude).toFixed(4)}`;
                    row.insertCell().textContent = point.avg_noise_level_db;
                    row.insertCell().textContent = point.environment_type;
                    row.insertCell().textContent = new Date(point.timestamp).toLocaleString();
                    row.insertCell().textContent = point.submitter || 'Guest/N/A';
                    
                    const actionCell = row.insertCell();
                    const deleteBtn = document.createElement('button');
                    deleteBtn.textContent = 'Delete';
                    deleteBtn.className = 'delete-btn';
                    deleteBtn.onclick = () => deleteSubmission(point.id);
                    actionCell.appendChild(deleteBtn);
                });
                status.textContent = `Total Submissions: ${data.length}`;
            } else {
                status.textContent = 'No noise data submitted yet.';
            }
        }
        
        async function deleteSubmission(id) {
            if (!confirm(`Are you sure you want to delete submission ID ${id}?`)) { return; }
            try {
                const response = await fetch('backend/delete_submission.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });

                const result = await response.json();

                if (response.ok && result.status === 'success') {
                    alert(`Submission ID ${id} deleted successfully.`);
                    noiseDataCache = []; adminDataCache = [];
                    await loadMapData(); await loadAdminSubmissions(); fetchUserList();
                } else {
                    alert(`Deletion failed: ${result.message || 'Unknown error.'}`);
                }
            } catch (error) { alert('Network error during deletion.'); }
        }
        
        async function fetchUserList() {
            const usersTableBody = document.querySelector('#usersTable tbody');
            const status = document.getElementById('usersStatus');
            usersTableBody.innerHTML = ''; status.textContent = 'Fetching user list...';
            try {
                const response = await fetch('backend/get_all_users.php'); 
                const data = await response.json();
                
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(user => {
                        const row = usersTableBody.insertRow();
                        row.insertCell().textContent = user.id;
                        row.insertCell().textContent = user.username;
                        row.insertCell().textContent = user.is_admin == 1 ? 'Admin' : 'User';
                        row.insertCell().textContent = user.created_at;
                    });
                    status.textContent = `Total Registered Users: ${data.length}`;
                } else {
                    status.textContent = 'No registered users found.';
                }
            } catch (error) {
                console.error('Failed to fetch user list:', error);
                status.textContent = 'Failed to load user list (Check backend/get_all_users.php).';
            }
        }

        // --- GRAPH MODAL FUNCTIONS ---
        async function showGraphModal(point) {
            document.getElementById('graphTitle').textContent = `Noise History for: ${point.location_name}`;
            document.getElementById('graphModal').style.display = 'block';

            try {
                const historicalData = noiseDataCache.filter(d => d.location_name === point.location_name);
                if (historicalData.length === 0) { alert('No historical data found for this location.'); closeModal(); return; }

                const labels = historicalData.map(d => new Date(d.timestamp).toLocaleTimeString());
                const noiseLevels = historicalData.map(d => d.avg_noise_level_db);

                renderChart(labels, noiseLevels);
            } catch (error) { console.error('Failed to process historical data:', error); alert('Failed to load historical data for graphing.'); }
        }

        function closeModal() {
            document.getElementById('graphModal').style.display = 'none';
        }

        function renderChart(labels, data) {
            const ctx = document.getElementById('noiseChart').getContext('2d');
            if (chartInstance) { chartInstance.destroy(); }

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Average Noise Level (dB)',
                        data: data,
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.15)',
                        borderWidth: 2,
                        tension: 0.2
                    }]
                },
                options: { responsive: true, scales: { y: { beginAtZero: false }, x: {} } }
            });
        }
    </script>
</body>
</html>