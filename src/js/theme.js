import '@material/web/all.js'




function destroyVideo(video) {
    video.pause();
    video.src = "";
    video.load();
    video.remove();
}

function checkSource(url) {
    let video = document.createElement("video");
    video.volume = 0;
    let hls = new Hls();
    url = decodeURIComponent(url);
    hls.loadSource(url);
    hls.attachMedia(video);

    // @link https://stackoverflow.com/questions/3258587/how-to-properly-unload-destroy-a-video-element

    // for (const [eventCode, eventName] of Object.entries(Hls.Events)) {
    //   hls.on(eventName, (event, data) => {
    //     console.log(eventCode, event, data);
    //   });
    // }

    let startTime = Date.now();

    hls.on(Hls.Events.MANIFEST_PARSED, (event, data) => {
        startTime = Date.now();
    });

    hls.on(Hls.Events.LEVEL_LOADED, (event, data) => {
        const loadingTime = Date.now() - startTime;
        console.log("loadingTime", loadingTime);

        console.log(url, event, data);
        let firstFrag = data.details.fragments[0];
        let baseUrl = firstFrag.baseurl;
        let fragUrl = firstFrag._url;
        console.log("baseUrL", baseUrl);
        console.log("fragUrl", fragUrl);

        hls.destroy();
        destroyVideo(video);
    });

    hls.on(Hls.Events.Error, (event, data) => {
        if (data.type == "networkError") {
            console.log("loadingTime", -1);
        }

        hls.destroy();
        destroyVideo(video);
    });
}


import Source from './source';

document.addEventListener('DOMContentLoaded', () => {

    const source = new Source(list);
    source.init();



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




import Scroll from './scroll';

const list = document.querySelector(".item-loop");
if (list) {
    const scroll = new Scroll(list);
    scroll.init();
}




