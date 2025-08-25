# Noisescope: Crowdsourced Noise Mapping

## Project Overview

**Noisescope** is a low-cost, community-driven noise monitoring system that leverages smartphone microphones and user feedback to estimate and classify environmental noise levels. By combining objective audio measurements with subjective user input, the system generates meaningful noise data that can be visualized on a web dashboard for analysis, reporting, and informed decision-making.

## Features

* Automatic noise level estimation using the phone's built-in microphone.
* User-provided contextual feedback (noise source, environment, perceived disturbance).
* Hybrid approach combining objective and subjective data for accurate classification.
* Real-time data visualization on an interactive map.
* Statistical charts showing patterns by location, noise type, and severity.
* Rule-based classification without relying on machine learning.

## Tech Stack

* **Frontend:** HTML, CSS, JavaScript, Leaflet.js (for interactive maps), Chart.js (for graphs)
* **Backend:** PHP (REST APIs)
* **Database:** MySQL
* **Hosting:** Localhost (XAMPP) for development/demo
* **Optional:** Android app for mobile-based data collection

## How It Works

### Data Collection

1. **Raw Audio Capture:**

   * Short audio samples are recorded from the phone's microphone.
   * The system calculates approximate decibel (dB) values from audio amplitude.

2. **User Contextual Input:**

   * Users answer a few quick questions:

     * Noise source: Traffic / Music / Construction / People / Other
     * Environment: Indoor / Outdoor
     * Perceived disturbance: Tolerable / Moderate / Very annoying

3. **Location & Timestamp:**

   * Automatically captured from the device.

### Data Processing

* The backend validates and stores the following in the database:

  * Decibel level (calculated from audio)
  * User responses (categorical data)
  * Location (latitude, longitude) and timestamp
* Rule-based logic combines raw audio and user input to classify noise severity:

  * Example:

    * 70 dB + Indoors + Very annoying → High noise problem
    * 70 dB + Outdoors + Tolerable → Background noise

### Visualization

* Interactive map shows markers representing reported noise levels.
* Filters allow viewing by noise type, severity, and time range.
* Charts display statistics, such as:

  * Most common noise sources
  * Average dB levels per location
  * Percentage of users disturbed

### Insights & Reporting

* Identify noise hotspots for cities, neighborhoods, or public spaces.
* Understand peak noise times and sources.
* Generate downloadable reports for authorities, researchers, or community awareness campaigns.

## Installation & Setup

1. Clone the repository:

```bash
git clone https://github.com/yourusername/noisescope.git
```

2. Set up XAMPP and start Apache & MySQL.
3. Import the provided `database.sql` file into MySQL.
4. Configure `config.php` with database credentials.
5. Open the frontend in a browser to start testing.

## Usage

1. Open the web app on a smartphone or browser.
2. Allow microphone and location access.
3. Start recording noise samples.
4. Fill in the quick contextual questions.
5. Submit the data to the server.
6. View real-time noise levels and patterns on the dashboard.

## Future Enhancements

* Calibration for more accurate dB measurements.
* Support for mobile apps (Android/iOS) for easier data collection.
* Advanced analytics to detect trends over longer periods.
* Notifications for high-noise alerts.
* Multi-user authentication for privacy and secure data submission.

## Category

* **Sustainability & Smart Cities**
* **Smart Governance & Digital India**
* **Healthcare for All**

## Contributing

Contributions are welcome. Please fork the repository and submit pull requests with improvements or bug fixes.

## License

This project is licensed under the MIT License.

---

**Author:** Varun & Team
