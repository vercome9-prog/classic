const DeviceCountManager = {
    async update() {
        try {
            const data = await API.getDeviceCount();
            const count = data.count || 0;
            const online = data.online || 0;
            const offline = data.offline || 0;
            
            const countElement = document.getElementById('deviceCount');
            const onlineElement = document.getElementById('onlineCount');
            const offlineElement = document.getElementById('offlineCount');
            
            if (countElement) {
                countElement.textContent = count;
            }
            if (onlineElement) {
                onlineElement.textContent = online;
            }
            if (offlineElement) {
                offlineElement.textContent = offline;
            }
        } catch (error) {
            console.error('Error loading device count:', error);
            const countElement = document.getElementById('deviceCount');
            const onlineElement = document.getElementById('onlineCount');
            const offlineElement = document.getElementById('offlineCount');
            
            if (countElement) {
                countElement.textContent = '?';
            }
            if (onlineElement) {
                onlineElement.textContent = '?';
            }
            if (offlineElement) {
                offlineElement.textContent = '?';
            }
        }
    }
};

