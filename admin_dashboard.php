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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            /* Dark Theme Colors */
            --primary-color: #a78bfa; /* Light Indigo for accents */
            --primary-hover: #8b5cf6;
            --accent-color: #34d399; /* Emerald for highlights */
            --danger-color: #f87171; /* Red */
            --bg-dark: #111827; /* Deep Charcoal Background */
            --card-dark: #1f2937; /* Slightly lighter card background */
            --text-light: #f3f4f6; /* Near White Text */
            --text-muted: #9ca3af; /* Gray Text */
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.15);
            --input-border: #4b5563;
            --radius: 10px; /* Slightly reduced radius */
        }

        * { box-sizing: border-box; }

        body { 
            font-family: 'Poppins', sans-serif; 
            margin: 0; 
            background-color: var(--bg-dark); 
            color: var(--text-light); 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-size: 13.5px; /* Reduced base font size */
        }

        /* Compact Dark Header */
        .header { 
            background: rgba(31, 41, 55, 0.95);
            backdrop-filter: blur(10px);
            padding: 0.7rem 2rem; /* Reduced padding */
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 1px 5px rgba(0,0,0,0.4);
            position: sticky;
            top: 0;
            z-index: 1000;
            height: 55px; /* Reduced header height */
        }

        .logo-group h1 { 
            margin: 0; 
            font-size: 1.25rem; /* Reduced font size */
            font-weight: 700;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-controls { 
            display: flex; 
            align-items: center; 
            gap: 1rem; /* Reduced gap */
        }

        .header-controls span { 
            font-size: 0.85rem; /* Reduced font size */
            color: var(--text-muted); 
        }
        
        .header-controls strong {
            color: var(--primary-color);
            font-weight: 600;
        }

        .nav-btn { 
            text-decoration: none; 
            padding: 0.3rem 1rem; /* Reduced padding */
            border-radius: 50px; 
            font-size: 0.8rem; /* Reduced font size */
            font-weight: 500; 
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
            background: transparent;
        }

        .nav-btn:hover {
            background: var(--danger-color);
            color: var(--bg-dark); 
            box-shadow: 0 0 15px rgba(248, 113, 113, 0.5);
        }

        /* Main Content - CRITICAL WIDTH REDUCTION */
        .content { 
            padding: 1.5rem; /* Reduced padding */
            max-width: 1200px; /* Reduced max width for laptop screens */
            margin: 0 auto; 
            width: 100%;
            flex-grow: 1;
        }

        /* Modern Tabs */
        .tabs { 
            display: flex; 
            margin-bottom: 1rem; /* Reduced margin */
            gap: 0.5rem; /* Reduced gap */
            flex-wrap: wrap;
        }

        .tab-button { 
            background-color: var(--card-dark); 
            border: 1px solid var(--input-border); 
            padding: 0.5rem 1rem; /* Reduced padding */
            cursor: pointer; 
            transition: all 0.3s ease; 
            border-radius: 50px;
            font-family: inherit;
            font-weight: 500;
            color: var(--text-muted);
            font-size: 0.85rem; /* Reduced font size */
        }

        .tab-button:hover { 
            background-color: #2e3e50; 
            color: var(--text-light);
            border-color: var(--primary-color);
        }

        .tab-button.active { 
            background-color: var(--primary-color); 
            color: var(--bg-dark); 
            box-shadow: 0 4px 10px rgba(167, 139, 250, 0.3);
            border-color: var(--primary-color);
            font-weight: 600;
        }

        /* Card Container for Tab Content */
        .tab-content { 
            background-color: var(--card-dark); 
            padding: 0; 
            border-radius: var(--radius); 
            box-shadow: var(--card-shadow); 
            border: 1px solid rgba(255,255,255,0.05);
            min-height: 350px; /* Reduced min height */
            overflow: hidden;
        }

        .tab-pane { 
            display: none; 
            padding: 1.5rem; /* Reduced padding */
            animation: fadeIn 0.3s ease;
        }
        
        .tab-pane.active { display: block; }

        h2 {
            margin-top: 0;
            color: var(--text-light);
            font-size: 1.3rem; /* Reduced font size */
            margin-bottom: 0.4rem;
        }

        p {
            color: var(--text-muted); 
            margin-bottom: 1rem; /* Reduced margin */
            font-size: 0.9rem;
        }

        /* Map - CRITICAL HEIGHT REDUCTION */
        #map { 
            height: 450px; /* Significantly reduced map height */
            width: 100%; 
            border-radius: 8px; 
            box-shadow: inset 0 0 5px rgba(0,0,0,0.5); 
            filter: grayscale(15%); 
        }

        /* Tables */
        .table-wrapper {
            overflow-x: auto;
            border-radius: 6px;
            border: 1px solid #374151;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            white-space: nowrap;
        }

        th, td { 
            padding: 0.75rem; /* Reduced table cell padding */
            text-align: left; 
            font-size: 0.85rem; /* Reduced font size */
            border-bottom: 1px solid #374151;
            color: var(--text-light);
        }
        
        td { color: var(--text-light); }

        th { 
            background-color: #2d3748; 
            color: var(--text-light);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem; /* Reduced font size */
            letter-spacing: 0.05em;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: #28303f; }

        /* Delete Button */
        .delete-btn { 
            padding: 0.3rem 0.6rem; /* Reduced padding */
            font-size: 0.75rem; /* Reduced font size */
        }

        /* Status Text */
        #submissionsStatus, #usersStatus {
            font-size: 0.85rem;
        }

        /* Modal */
        .modal-content { 
            margin: 3% auto; /* Move modal up slightly */
            padding: 1.5rem; /* Reduced padding */
        }
        
        /* Chart.js adjustments for dark theme background */
        #noiseChart {
            background-color: #2d3748; 
            padding: 10px;
            border-radius: 8px;
        }
        
        /* Mobile Adjustments (Kept as before, but overall design is now better for tablets/laptops) */
        @media (max-width: 768px) {
            .header { padding: 0.5rem 1rem; }
            .content { padding: 1rem; }
            .tabs { flex-direction: column; }
            .tab-button { width: 100%; border-radius: 8px; }
            #map { height: 350px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-group">
            <h1>NoiseScope Admin ⚙️</h1>
        </div>
        <div class="header-controls">
            <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</span>
            <a href="backend/logout.php" class="nav-btn">Log Out</a>
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
                <h2>Submissions Database</h2>
                <p id="submissionsStatus">Loading data...</p>
                <div class="table-wrapper">
                    <table id="submissionsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Location Name</th>
                                <th>Lat, Lng</th>
                                <th>dB Level</th>
                                <th>Type</th>
                                <th>Timestamp</th>
                                <th>Submitter</th> 
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="Users" class="tab-pane">
                <h2>Registered Users</h2>
                <p id="usersStatus">Loading user list...</p>
                <div class="table-wrapper">
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
    </div>

    <div id="graphModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h3 id="graphTitle">Noise Level History</h3>
            <canvas id="noiseChart"></canvas>
            <p style="margin-top: 10px; font-size: 0.8rem; font-style: italic; color: var(--text-muted);">*Note: This graph shows the time history of all collected noise averages at this specific location.</p>
        </div>
    </div>

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
                const noiseLevels = historicalData.map(d => point.avg_noise_level_db); 

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
                        borderColor: 'var(--primary-color)', 
                        backgroundColor: 'rgba(167, 139, 250, 0.1)', 
                        borderWidth: 2,
                        tension: 0.3, 
                        pointBackgroundColor: 'var(--card-dark)', 
                        pointBorderColor: 'var(--primary-color)'
                    }]
                },
                options: { 
                    responsive: true, 
                    scales: { 
                        y: { 
                            beginAtZero: false,
                            grid: { color: '#374151' }, 
                            ticks: { color: 'var(--text-muted)', font: { size: 11 } }, /* Smaller font */
                        }, 
                        x: {
                            grid: { color: '#374151' },
                            ticks: { color: 'var(--text-muted)', font: { size: 11 } }, /* Smaller font */
                        } 
                    } 
                }
            });
        }
    </script>
</body>
</html>

