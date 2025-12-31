const API = {
    baseUrl: 'api.php',
    
    async request(url, options = {}) {
        try {
            const response = await fetch(url, {
                ...options,
                signal: AbortSignal.timeout(30000)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            return data;
        } catch (error) {
            if (error.name === 'AbortError') {
                throw new Error('Request timeout');
            }
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                throw new Error('Network error');
            }
            throw error;
        }
    },
    
    async getDevices(page = 1, perPage = 20, search = '') {
        const url = `${this.baseUrl}?action=getDevices&page=${page}&perPage=${perPage}&search=${encodeURIComponent(search)}`;
        return await this.request(url);
    },
    
    async getLogs(page = 1, perPage = 50, search = '', typeFilter = '') {
        const url = `${this.baseUrl}?action=getLogs&page=${page}&perPage=${perPage}&search=${encodeURIComponent(search)}&typeFilter=${encodeURIComponent(typeFilter)}`;
        return await this.request(url);
    },
    
    async getDeviceCount() {
        const url = `${this.baseUrl}?action=getDeviceCount`;
        return await this.request(url);
    },
    
    async sendCommand(androidIds, command) {
        return await this.request(`${this.baseUrl}?action=sendCommand`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                androidIds: androidIds,
                command: command
            })
        });
    }
};

