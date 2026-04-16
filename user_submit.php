<?php
session_start();

// Determine the user's status for the header
$is_guest = isset($_GET['mode']) && $_GET['mode'] === 'anonymous';
$is_logged_in = isset($_SESSION['username']);

if ($is_logged_in && !$is_guest) {
    $welcome_message = "Welcome, <strong>" . htmlspecialchars($_SESSION['username']) . "</strong>";
    $logout_link = '<a href="backend/logout.php" class="nav-btn logout">Log Out</a>';
} else {
    // Guest or not logged in
    $welcome_message = "Welcome, <strong>Guest</strong>";
    $logout_link = '<a href="user_login.html" class="nav-btn login">Log In / Register</a>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NoiseScope - User Submission</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
            --radius: 8px; /* Slightly more reduced radius for compactness */
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
            font-size: 13px; /* Slightly more reduced base font size */
        }
        
        /* Compact Dark Header */
        .header { 
            background: rgba(31, 41, 55, 0.95);
            backdrop-filter: blur(10px);
            padding: 0.6rem 2rem; /* Further reduced padding */
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 1px 5px rgba(0,0,0,0.4);
            position: sticky;
            top: 0;
            z-index: 1000;
            height: 50px; /* Further reduced height */
        }

        .logo-group h1 { 
            margin: 0; 
            font-size: 1.2rem; /* Further reduced font size */
            font-weight: 700;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-controls { 
            display: flex; 
            align-items: center; 
            gap: 0.8rem; /* Further reduced gap */
        }

        .user-status { 
            font-size: 0.8rem; /* Further reduced font size */
            color: var(--text-muted); 
        }
        
        .user-status strong {
            color: var(--primary-color);
            font-weight: 600;
        }

        .nav-btn { 
            text-decoration: none; 
            padding: 0.25rem 0.9rem; /* Further reduced padding */
            border-radius: 50px; 
            font-size: 0.75rem; /* Further reduced font size */
            font-weight: 500; 
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }

        .nav-btn.logout {
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
            background: transparent;
        }
        .nav-btn.logout:hover {
            background: var(--danger-color);
            color: var(--bg-dark);
            box-shadow: 0 0 15px rgba(248, 113, 113, 0.5);
        }

        .nav-btn.login {
            background: var(--primary-color);
            color: var(--bg-dark);
            font-weight: 600;
        }
        .nav-btn.login:hover {
            background: var(--primary-hover);
            box-shadow: 0 0 15px rgba(167, 139, 250, 0.5);
        }

        /* Main Layout - Wider and Less Tall */
        .content-wrapper {
            display: grid;
            grid-template-columns: 320px 1fr; /* Wider form column (was 280px) */
            max-width: 1100px; /* Increased max width (was 1000px) */
            margin: 1rem auto; /* Reduced vertical margin */
            padding: 0 2rem; /* Increased side padding for more width */
            gap: 1.5rem; 
            width: 100%;
            align-items: start;
            flex-grow: 1;
        }

        /* Cards - Dark Style */
        .card-style {
            background-color: var(--card-dark);
            padding: 0.9rem; /* Further reduced card padding */
            border-radius: var(--radius);
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255,255,255,0.05); 
        }

        /* Form Section */
        .submission-section {
            display: flex;
            flex-direction: column;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 7px; /* Further reduced gap */
            margin-bottom: 0.7rem; /* Further reduced margin */
            padding-bottom: 0.4rem; /* Further reduced padding */
            border-bottom: 1px solid #374151;
        }

        .section-header h2 {
            font-size: 1.05rem; /* Further reduced font size */
            color: var(--text-light);
            margin: 0;
            font-weight: 600;
        }

        .step-number {
            background: var(--accent-color);
            color: var(--bg-dark);
            width: 22px; /* Further smaller step number */
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem; /* Further reduced font size */
            font-weight: 700;
            box-shadow: 0 0 8px rgba(52, 211, 153, 0.5);
        }

        .form-group { margin-bottom: 0.7rem; } /* Further reduced margin */
        
        label {
            display: block;
            margin-bottom: 0.15rem; /* Further reduced margin */
            font-weight: 500;
            font-size: 0.8rem; /* Further reduced font size */
            color: var(--text-muted);
        }

        input[type="text"], select {
            width: 100%;
            padding: 0.45rem 0.6rem; /* Further reduced input padding */
            border: 1px solid var(--input-border);
            border-radius: 5px; /* Further smaller radius */
            font-size: 0.85rem; /* Further reduced font size */
            font-family: inherit;
            background-color: #1a2430;
            color: var(--text-light);
            transition: all 0.3s;
        }

        input[type="text"]:focus, select:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: #1f2937;
            box-shadow: 0 0 0 3px rgba(167, 139, 250, 0.2);
        }
        
        /* Primary Action Button (Yellow/Amber) */
        #startMeasureButton {
            background: linear-gradient(135deg, #fcd34d 0%, #d97706 100%);
            color: var(--bg-dark);
            border: none;
            padding: 0.55rem; /* Further reduced button padding */
            width: 100%;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.88rem; /* Further reduced font size */
            font-weight: 700;
            transition: transform 0.2s, box-shadow 0.2s;
            margin-top: 0.2rem; /* Further reduced margin */
            letter-spacing: 0.5px;
        }

        #startMeasureButton:hover:not(:disabled) { 
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(245, 158, 11, 0.3);
        }

        #startMeasureButton:disabled {
            background: #4b5563;
            color: #6b7280;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .helper-text {
            text-align: center;
            font-size: 0.75rem; /* Further reduced font size */
            color: var(--text-muted);
            margin-top: 0.4rem; /* Further reduced margin */
            margin-bottom: 0;
            font-style: italic;
        }

        /* Measurement Display Box */
        .measurement-output {
            text-align: center;
            margin-top: 0.9rem; /* Further reduced margin */
            padding: 0.7rem; /* Further reduced output box padding */
            background: #1a2430; 
            border-radius: 6px;
            border: 1px dashed var(--input-border);
        }

        .measurement-output p.label {
            font-size: 0.7rem; /* Further reduced font size */
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin: 0;
            font-weight: 500;
        }

        #dBDisplay {
            font-size: 2rem; /* Further reduced font size */
            font-weight: 700;
            color: var(--accent-color);
            display: block;
            line-height: 1.1;
            margin: 0.25rem 0 0.5rem 0; /* Further reduced margins */
            text-shadow: 0 0 10px rgba(52, 211, 153, 0.4);
        }
        
        .db-unit {
            font-size: 1rem; /* Further reduced font size */
            color: var(--text-muted);
            font-weight: 400;
        }

        #coordsDisplay {
            display: inline-block;
            background: #374151;
            color: var(--primary-color);
            padding: 1px 7px; /* Further reduced padding */
            border-radius: 12px; /* Further smaller radius */
            font-size: 0.7rem; /* Further reduced font size */
            font-weight: 500;
            margin-top: 2px;
        }

        /* Submit Button (Primary) */
        #submitButton {
            background: var(--primary-color);
            color: var(--bg-dark);
            border: none;
            padding: 0.6rem; /* Further reduced padding */
            width: 100%;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.88rem; /* Further reduced font size */
            font-weight: 700;
            margin-top: 0.7rem; /* Further reduced margin */
            transition: all 0.3s;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 10px rgba(167, 139, 250, 0.3);
        }

        #submitButton:hover:not(:disabled) {
            background: var(--primary-hover);
        }

        #submitButton:disabled {
            background: #374151;
            color: #6b7280;
            cursor: not-allowed;
            opacity: 0.8;
            box-shadow: none;
        }

        /* Map Section */
        .map-container {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 380px; /* Further reduced min height */
        }

        #map {
            flex-grow: 1;
            height: 100%;
            min-height: 380px; /* Further reduced min height */
            width: 100%;
            border-radius: 6px;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.5);
            z-index: 1;
            filter: grayscale(15%);
        }

        /* Status Messages */
        #statusMessage {
            margin-top: 0.5rem; /* Further reduced margin */
            padding: 0.45rem; /* Further reduced padding */
            border-radius: 5px;
            font-size: 0.75rem; /* Further reduced font size */
            text-align: center;
            font-weight: 500;
            display: none;
        }
        #statusMessage.error { display: block; background: #450a0a; color: #fecaca; border: 1px solid #b91c1c; }
        #statusMessage.success { display: block; background: #064e3b; color: #a7f3d0; border: 1px solid #059669; }

        /* Responsive Adjustments (Kept for mobile use) */
        @media (max-width: 900px) {
            .content-wrapper {
                grid-template-columns: 1fr; /* Stack vertically */
                max-width: 95%;
                padding: 0 1rem;
                gap: 1.2rem; /* Adjusted gap for vertical stacking */
            }
            
            .header {
                padding: 0.6rem 1rem;
            }
            
            .map-container {
                min-height: 350px; /* Adjusted for mobile stacking */
            }
            
            #map {
                min-height: 320px; /* Adjusted for mobile stacking */
            }
        }

        @media (max-width: 600px) {
            .header {
                padding: 0.4rem 0.8rem;
                flex-direction: column;
                height: auto;
                gap: 0.4rem;
            }
            
            .header-controls {
                width: 100%;
                justify-content: center;
            }

            .content-wrapper {
                padding: 0 0.5rem;
                margin: 0.8rem auto;
            }
            
            .card-style {
                padding: 0.9rem;
            }
            
            #dBDisplay {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-group">
            <h1>NoiseScope MVP 🎙️</h1>
        </div>
        <div class="header-controls">
            <span class="user-status"><?php echo $welcome_message; ?></span>
            <?php echo $logout_link; ?>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="submission-section card-style">
            <div class="section-header">
                <div class="step-number">1</div>
                <h2>Measure & Submit</h2>
            </div>
            
            <form id="noiseForm">
                <div class="form-group">
                    <label for="locationName">Location Name</label>
                    <input type="text" id="locationName" placeholder="e.g., Central Library" required oninput="checkFormReady()">
                </div>
                
                <div class="form-group">
                    <label for="environmentType">Environment Type</label>
                    <select id="environmentType" required onchange="checkFormReady()">
                        <option value="">Select environment...</option>
                        <option value="Residential (Quiet)">Residential (Quiet)</option>
                        <option value="Residential (Traffic)">Residential (Traffic)</option>
                        <option value="Commercial/Market">Commercial/Market</option>
                        <option value="Indoor (e.g., classroom, office)">Indoor</option>
                        <option value="Industrial Zone">Industrial Zone</option>
                        <option value="Public/Park">Public/Park</option>
                    </select>
                </div>

                <button type="button" id="startMeasureButton">
                    Start Measurement (5s)
                </button>
                
                <p class="helper-text">
                    Click map to pin location.
                </p>

                <div class="measurement-output">
                    <p class="label">Avg Noise Level</p>
                    <span id="dBDisplay">-- <span class="db-unit">dB</span></span>
                    <p class="label" style="margin-top: 8px;">Coordinates</p>
                    <span id="coordsDisplay">None</span>
                </div>
                
                <p id="statusMessage"></p>

                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">
                <input type="hidden" id="avgNoiseLevel" name="avgNoiseLevel">

                <button type="submit" id="submitButton" disabled>Submit Data</button>
            </form>
        </div>

        <div class="map-section card-style map-container">
            <div class="section-header">
                <div class="step-number">2</div>
                <h2>Community Map</h2>
            </div>
            <p style="color: var(--text-muted); margin-bottom: 0.5rem; font-size: 0.8rem; margin-top: -0.2rem;">
                Click on the map to place your measurement location.
            </p>
            <div id="map"></div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="user_submit.js"></script>
</body>
</html>
