window.onload = function() {
    DevicesManager.load();
    DeviceCountManager.update();
    
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

window.onclick = function(event) {
    const modal = document.getElementById('commandModal');
    if (event.target === modal) {
        closeCommandModal();
    }
}

