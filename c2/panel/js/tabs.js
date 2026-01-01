const TabsManager = {
    switchTab(tab) {
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        
        // Find the button that was clicked or the button corresponding to the tab
        const clickedBtn = event ? event.currentTarget : document.querySelector(`.tab-button[onclick*="'${tab}'"]`);
        if (clickedBtn) clickedBtn.classList.add('active');
        
        const targetTab = document.getElementById(tab + '-tab');
        if (targetTab) targetTab.classList.add('active');
        
        if (tab === 'devices') {
            DevicesManager.load();
        } else if (tab === 'logs') {
            LogsManager.load();
        }
    }
};

