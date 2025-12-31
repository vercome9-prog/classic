const TabsManager = {
    switchTab(tab) {
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        
        event.target.classList.add('active');
        document.getElementById(tab + '-tab').classList.add('active');
        
        if (tab === 'devices') {
            DevicesManager.load();
        } else {
            LogsManager.load();
        }
    }
};

