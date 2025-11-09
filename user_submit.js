// user_submit.js - Web Audio API Measurement and Map Initialization

let map;
let marker; 
let avgNoiseLevel = null;
let coords = null;
const DEFAULT_CENTER = [28.6139, 77.2090]; // New Delhi coordinates (Fallback)

// --- Web Audio API Global Variables ---
let audioContext = null;
let analyser = null;
let source = null;
let stream = null;
let dataArray = null;
let animationId = null; 
let readings = [];
const MEASUREMENT_DURATION = 5000; // 5 seconds

// --- Microphone Measurement Functionality ---

// Rough conversion for demonstration purposes
function volumeToDb(volume) {
    return 30 + (volume / 255) * 70; 
}

function startMeasurement() {
    const startButton = document.getElementById('startMeasureButton');
    const statusMessage = document.getElementById('statusMessage');
    const dBDisplay = document.getElementById('dBDisplay');

    // Reset UI and state
    readings = [];
    startButton.disabled = true;
    statusMessage.textContent = 'Awaiting microphone access...';
    
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        statusMessage.textContent = 'Error: Microphone API not supported by this browser.';
        startButton.disabled = false;
        return;
    }

    // 1. Request microphone access
    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(audioStream => {
            stream = audioStream;
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioContext.createAnalyser();
            source = audioContext.createMediaStreamSource(stream);

            analyser.fftSize = 2048;
            dataArray = new Uint8Array(analyser.frequencyBinCount);
            
            source.connect(analyser);
            
            statusMessage.textContent = 'Recording noise... (5s)';
            
            // Start the live display loop
            runLiveDisplay();

            // 2. Stop and Calculate Average after 5 seconds
            setTimeout(stopMeasurement, MEASUREMENT_DURATION);

        })
        .catch(err => {
            console.error('Microphone access denied or failed:', err);
            statusMessage.textContent = 'Error: Microphone access denied or failed. Check browser permissions.';
            startButton.disabled = false;
        });
}

function runLiveDisplay() {
    const dBDisplay = document.getElementById('dBDisplay');

    function updateVolume() {
        if (!analyser) return;

        analyser.getByteFrequencyData(dataArray); 
        
        // Find the peak volume
        let maxVolume = 0;
        for (let i = 0; i < dataArray.length; i++) {
            if (dataArray[i] > maxVolume) {
                maxVolume = dataArray[i];
            }
        }
        
        const currentDb = Math.round(volumeToDb(maxVolume));
        
        dBDisplay.textContent = currentDb;
        readings.push(currentDb);

        animationId = requestAnimationFrame(updateVolume);
    }
    
    animationId = requestAnimationFrame(updateVolume);
}

function stopMeasurement() {
    const startButton = document.getElementById('startMeasureButton');
    const statusMessage = document.getElementById('statusMessage');
    const dBDisplay = document.getElementById('dBDisplay');

    // 1. Stop processing and clean up
    cancelAnimationFrame(animationId);
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
    }
    if (audioContext) {
        audioContext.close();
    }
    
    // 2. Calculate Average
    const sum = readings.reduce((a, b) => a + b, 0);
    const average = readings.length > 0 ? Math.round(sum / readings.length) : 0;
    
    // 3. Update UI and state
    dBDisplay.textContent = average;
    document.getElementById('avgNoiseLevel').value = average;
    
    if (average > 0) {
        statusMessage.textContent = `Measurement complete! Average: ${average} dB.`;
    } else {
         statusMessage.textContent = `Measurement stopped. Average: 0 dB.`;
    }
    
    avgNoiseLevel = average;
    startButton.disabled = false;
    checkFormReady();
}

document.getElementById('startMeasureButton').addEventListener('click', startMeasurement);


// --- Submission Logic (Unchanged) ---
async function submitData(e) {
    e.preventDefault();
    
    if (!avgNoiseLevel || !coords) {
        alert('Please complete noise measurement and select a location on the map.');
        return;
    }

    const locationName = document.getElementById('locationName').value;
    const environmentType = document.getElementById('environmentType').value;

    const payload = {
        locationName: locationName,
        latitude: coords.lat,
        longitude: coords.lng,
        avgNoiseLevel: avgNoiseLevel,
        environmentType: environmentType
    };

    const submitButton = document.getElementById('submitButton');
    submitButton.disabled = true;
    submitButton.textContent = 'Submitting...';

    try {
        const response = await fetch('backend/submit_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        const statusMessage = document.getElementById('statusMessage');

        if (response.ok && result.status === 'success') {
            statusMessage.style.color = 'green';
            statusMessage.textContent = 'Submission successful! Thank you for your data.';
            document.getElementById('noiseForm').reset();
            loadDataAndVisualize();
        } else {
            statusMessage.style.color = 'red';
            statusMessage.textContent = result.message || 'Submission failed.';
        }
    } catch (error) {
        statusMessage.style.color = 'red';
        statusMessage.textContent = 'Network error. Could not reach the server.';
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = 'Submit Noise Data';
        avgNoiseLevel = null;
        coords = null;
        if (marker) map.removeLayer(marker);
        document.getElementById('coordsDisplay').textContent = 'No Point Selected';
        document.getElementById('dBDisplay').textContent = '--';
    }
}

document.getElementById('noiseForm').addEventListener('submit', submitData);


// --- Map Initialization and Visualization (Unchanged) ---

function initializeMap() {
    map = L.map('map').setView(DEFAULT_CENTER, 13); 

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const userLatLng = [position.coords.latitude, position.coords.longitude];
                map.setView(userLatLng, 15); 
                document.getElementById('statusMessage').textContent = 'Map centered near your location. Click on the map to PIN your submission point.';
            },
            function(error) {
                console.error("Geolocation failed: ", error);
                document.getElementById('statusMessage').textContent = 'Geolocation denied. Map set to default location. Click to PIN submission point.';
            },
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
        );
    } else {
        document.getElementById('statusMessage').textContent = 'Geolocation not supported by this browser. Click on the map to PIN submission point.';
    }

    map.on('click', function(e) {
        if (marker) map.removeLayer(marker);
        coords = e.latlng;
        
        marker = L.marker(coords).addTo(map)
            .bindPopup("Your Submission Location")
            .openPopup();
        
        document.getElementById('latitude').value = coords.lat;
        document.getElementById('longitude').value = coords.lng;
        document.getElementById('coordsDisplay').textContent = `${coords.lat.toFixed(4)}, ${coords.lng.toFixed(4)}`;
        checkFormReady();
    });

    loadDataAndVisualize();
}

// --- Visualization Helpers (Unchanged) ---
function getMarkerColor(dB) {
    if (dB < 45) return '#28a745';
    if (dB < 60) return '#007bff';
    if (dB < 75) return '#ffc107';
    return '#dc3545';
}

function updateMap(data) {
    map.eachLayer(layer => {
        if (layer instanceof L.Marker && layer !== marker) {
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

        L.marker([point.latitude, point.longitude], {icon: customIcon})
            .bindPopup(`
                <b>${point.location_name}</b><br>
                Noise Level: ${point.avg_noise_level_db} dB<br>
                Type: ${point.environment_type.charAt(0).toUpperCase() + point.environment_type.slice(1)}
            `)
            .addTo(map);
    });
}

async function loadDataAndVisualize() {
    try {
        const response = await fetch('backend/get_data.php'); 
        const data = await response.json();
        
        if (Array.isArray(data)) {
            updateMap(data);
        }

    } catch (error) {
        console.error('Failed to fetch data for user map:', error);
    }
}

function checkFormReady() {
    const isReady = avgNoiseLevel !== null && coords !== null && document.getElementById('locationName').value && document.getElementById('environmentType').value;
    document.getElementById('submitButton').disabled = !isReady;
}

document.addEventListener('DOMContentLoaded', initializeMap);