import '@material/web/all.js'




function destroyVideo(video) {
    video.pause();
    video.src = "";
    video.load();
    video.remove();
}




import SourcesManager from './sources-manager';

import SourceProviderChecker from './source-provider-checker';


document.addEventListener('DOMContentLoaded', () => {

    // SourceProviderChecker.checkAllProviders();

    document.querySelector('.select-season')?.addEventListener('change', (event) => {
        window.location.href = event.target.value;
    })


    let sourceArea = document.querySelector('.source-area');

    if (sourceArea) {
        const sourceManager = new SourcesManager(list);
        sourceManager.init();

        window.wptvSource = source;
    }


    /**
     * Content Tabs.
     */
    let tabs = document.querySelector('.content-tabs');

    if (tabs) {
        tabs.addEventListener('change', (event) => {
            let activeTab = event.target.activeTab;

            let targetPanelId = activeTab.getAttribute('aria-controls');

            document.querySelectorAll('.info-area [role="tabpanel"]').forEach(panel => {
                panel.hidden = panel.id !== targetPanelId;
            });

        });
    }




});

import Scroll from './scroll';

const list = document.querySelector(".item-loop");
if (list) {
    const scroll = new Scroll(list);
    scroll.init();
}




