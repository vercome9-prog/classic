const LogsManager = {
    currentPage: 1,
    perPage: 50,
    allLogs: [],
    filteredLogs: [],
    
    async load() {
        const search = document.getElementById('logSearch').value;
        const typeFilter = document.getElementById('logTypeFilter').value;
        try {
            const data = await API.getLogs(this.currentPage, this.perPage, search, typeFilter);
            
            if (data.error) {
                console.error('API error:', data.error);
                this.allLogs = [];
                this.filteredLogs = [];
                this.render();
                return;
            }
            
            this.allLogs = Array.isArray(data.logs) ? data.logs : [];
            this.filteredLogs = this.allLogs;
            this.render();
            
            const totalPages = data.totalPages || 0;
            const total = data.total || 0;
            Pagination.render('logsPagination', this.currentPage, totalPages, total, (page) => {
                this.currentPage = page;
                this.load();
            });
        } catch (error) {
            console.error('Error loading logs:', error);
            this.allLogs = [];
            this.filteredLogs = [];
            this.render();
        }
    },
    
    render() {
        const tbody = document.getElementById('logsTableBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        if (!Array.isArray(this.filteredLogs) || this.filteredLogs.length === 0) {
            const tr = document.createElement('tr');
            tr.innerHTML = '<td colspan="5" style="text-align: center; padding: 20px;">No logs found</td>';
            tbody.appendChild(tr);
            return;
        }
        
        this.filteredLogs.forEach(log => {
            if (!log) return;
            
            const tr = document.createElement('tr');
            const id = String(log.id || '');
            const androidId = String(log.android_id || '').substring(0, 50);
            const typelog = String(log.typelog || '-');
            const typeClass = typelog ? typelog.replace(/_/g, '-').replace(/[^a-z0-9-]/gi, '') : '';
            const logText = String(log.log || '-');
            const truncatedLog = logText.length > 100 ? logText.substring(0, 100) + '...' : logText;
            
            let date = 'N/A';
            if (log.created_at) {
                try {
                    const dateObj = new Date(log.created_at);
                    if (!isNaN(dateObj.getTime())) {
                        date = dateObj.toLocaleString();
                    }
                } catch (e) {
                    date = `ID: ${id}`;
                }
            } else if (log.id) {
                date = `ID: ${id}`;
            }
            
            tr.innerHTML = `
                <td>${id}</td>
                <td><span class="android-id">${androidId.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span></td>
                <td><span class="log-type ${typeClass}">${typelog.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span></td>
                <td class="log-cell" title="${logText.replace(/"/g, '&quot;')}">${truncatedLog.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</td>
                <td>${date}</td>
            `;
            tbody.appendChild(tr);
        });
    },
    
    filter() {
        this.currentPage = 1;
        this.load();
    }
};

