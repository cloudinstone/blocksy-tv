import '@material/web/all.js'


document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.source-provider-switch-list li').forEach(item => {
        item.addEventListener('click', () => {
            document.querySelector('.source-provider-switch-container').classList.remove('active')
        })
    });




    /**
     * Content Tabs.
     */
    let tabs = document.querySelector('.content-tabs');

    tabs.addEventListener('change', (event) => {
        let activeTab = event.target.activeTab;

        let targetPanelId = activeTab.getAttribute('aria-controls');

        document.querySelectorAll('.info-area [role="tabpanel"]').forEach(panel => {
            panel.hidden = panel.id !== targetPanelId;
        });

    });


});

