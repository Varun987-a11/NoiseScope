<?php
session_start();

// Determine the user's status for the header
$is_guest = isset($_GET['mode']) && $_GET['mode'] === 'anonymous';
$is_logged_in = isset($_SESSION['username']);

if ($is_logged_in && !$is_guest) {
    $welcome_message = "Welcome, **" . htmlspecialchars($_SESSION['username']) . "**! Submit your data.";
    $logout_link = '<a href="backend/logout.php" class="header-link">Log Out</a>';
} else {
    // Guest or not logged in
    $welcome_message = "Welcome, **Guest**! Submit anonymous noise data.";
    $logout_link = '<a href="user_login.html" class="header-link">Log In / Register</a>';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NoiseScope - User Submission</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    
    <style>
        /* Global Styles */
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f7f6; color: #333; }
        
        /* Header/Navigation */
        .header { 
            background-color: #007bff; 
            color: white; 
            padding: 15px 20px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header h1 { margin: 0; font-size: 24px; }
        .header-controls { display: flex; align-items: center; }
        .header-link { 
            color: white; 
            text-decoration: none; 
            padding: 8px 15px; 
            border: 1px solid white; 
            border-radius: 4px; 
            transition: background-color 0.3s; 
            margin-left: 10px;
        }
        .header-link:hover { background-color: #0056b3; }
        .user-status { margin-right: 15px; font-weight: 500; }

        /* Main Content Grid */
        .content-wrapper {
            display: flex;
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            gap: 20px;
        }
        .submission-section, .map-section {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        /* Column 1: Submission Form */
        .submission-section {
            flex: 1; 
            min-width: 350px;
        }
        .submission-section h2 {
            color: #28a745; 
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-top: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        input[type="text"], select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        
        /* Buttons and Status */
        #startMeasureButton {
            background-color: #ffc107;
            color: #333;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s;
            margin-bottom: 10px;
        }
        #startMeasureButton:hover:not(:disabled) { background-color: #e0a800; }
        #startMeasureButton:disabled { background-color: #ccc; cursor: not-allowed; }
        
        #submitButton {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        #submitButton:hover:not(:disabled) { background-color: #0056b3; }
        #submitButton:disabled { background-color: #ccc; cursor: not-allowed; }

        /* Measurement Display */
        .measurement-output {
            text-align: center;
            margin-top: 30px;
            padding: 15px;
            border: 1px dashed #ccc;
            border-radius: 4px;
        }
        .measurement-output p { margin: 5px 0; }
        #dBDisplay {
            font-size: 36px;
            font-weight: bold;
            color: #dc3545;
            display: block;
            margin: 10px 0;
        }
        #coordsDisplay {
            color: #007bff;
            font-weight: bold;
        }
        #statusMessage {
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        /* Column 2: Map */
        .map-section {
            flex: 2; 
        }
        #map {
            height: 500px;
            width: 100%;
            border-radius: 4px;
        }

        /* Mobile Adjustments */
        @media (max-width: 900px) {
            .content-wrapper {
                flex-direction: column;
            }
            .submission-section {
                min-width: auto;
            }
            .header-controls {
                flex-direction: column;
                align-items: flex-end;
            }
            .user-status {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>NoiseScope MVP 🎙️🗺️</h1>
        <div class="header-controls">
            <span class="user-status"><?php echo $welcome_message; ?></span>
            <?php echo $logout_link; ?>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="submission-section">
            <h2>1. Measure and Submit Data</h2>
            <form id="noiseForm">
                
                <div class="form-group">
                    <label for="locationName">Location Name/Description:</label>
                    <input type="text" id="locationName" placeholder="e.g., Library, Bus Stop, Park" required oninput="checkFormReady()">
                </div>
                
                <div class="form-group">
                    <label for="environmentType">Environment Type (MCQ):</label>
                    <select id="environmentType" required onchange="checkFormReady()">
                        <option value="">-- Select --</option>
                        <option value="Residential (Quiet)">Residential (Quiet)</option>
                        <option value="Residential (Traffic)">Residential (Traffic)</option>
                        <option value="Commercial/Market">Commercial/Market</option>
                        <option value="Indoor (e.g., classroom, office)">Indoor (e.g., classroom, office)</option>
                        <option value="Industrial Zone">Industrial Zone</option>
                        <option value="Public/Park">Public/Park</option>
                    </select>
                </div>

                <button type="button" id="startMeasureButton">Start Noise Measurement (5s)</button>
                <p style="text-align: center; font-size: 12px; margin-top: 5px; color: #555;">
                    Click on the map below to select your submission location!
                </p>

                <div class="measurement-output">
                    <p>Calculated Avg dB:</p>
                    <span id="dBDisplay">--</span> dB
                    <p>Selected Location: <span id="coordsDisplay">No Point Selected</span></p>
                </div>
                
                <p id="statusMessage" style="color: red;"></p>

                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">
                <input type="hidden" id="avgNoiseLevel" name="avgNoiseLevel">

                <button type="submit" id="submitButton" disabled>Submit Noise Data</button>
            </form>
        </div>

        <div class="map-section">
            <h2>2. Community Noise Map</h2>
            <p>View noise levels submitted by other users (Color-coded).</p>
            <div id="map"></div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script src="user_submit.js"></script>
</body>
</html>