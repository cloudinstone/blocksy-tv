import Cookies from 'js-cookie';
import Dexie from 'dexie';
import DPlayer from 'dplayer';
import DPlayerExtend from './dplayer-extend';
import SourceProviderChecker from './source-provider-checker';
import WatchRecord from './watch-record';
import SourceList from './source-list';
import EpisodeList from './episode-list';

class SourcesManager {
    sourceListEl;
    episodeListEl;
    video;
    postId = 0;
    sources = [];
    firstLoadedSource;
    currentSource;
    currentSourceNetworkError = false;
    currentEpisodeIndex = 0;
    idb;
    introDuration = 0;
    outroDuration = 0;
    messageEl;
    watchRecord = {};

    constructor() {
        let sourceArea = document.querySelector('.source-area');

        this.postId = parseInt(sourceArea.dataset.postId);
        this.video = document.getElementById("video");
        this.sourceListEl = document.querySelector('.source-list')
        this.episodeListEl = document.querySelector('.episode-list')
        this.messageEl = document.querySelector('.source-message')

        this.setupDb();
    }

    init() {
        this.load();

        // this.initEvents();
    }

    initEvents() {
        window.addEventListener("beforeunload", () => this.onBeforeunload());



        this.sourceListEl.addEventListener('click', (event) => {
            let itemEl = event.target.closest('li');

            if (itemEl.classList.contains('active')) {
                sourceListEl.classList.toggle('open');
                return false;
            }

            const source = itemEl.source;

            const resourceChangeEvent = new CustomEvent('source_change', { detail: { source: source } });
            document.dispatchEvent(resourceChangeEvent);

            this.switchSource(source)
        });
    }

    async load() {



        let sources = await this.fetchSources();
        console.log('sources', sources);
        let episodesData = await this.fetchEpisodesData();
        console.log('episodesData', episodesData);

        // let speedTestResults = this.getSpeedTestResults();
        // console.log('speedTestResults', speedTestResults);

        // if (speedTestResults) {
        //     this.updateSourcesBySpeed(sources, speedTestResults);
        // }

        // this.sources = sources;
        // this.currentSource = sources[0];



        const sourceList = document.getElementById('source-list');
        const episodeList = document.getElementById('episode-list');

        sourceList.addEventListener('source_change', (event) => {
            const source = event.detail.source;
            let episodes = source.episodes;

            if (episodesData) {
                episodes = episodes.map(episode => {
                    let episodeData = episodesData.find(ep => {
                        console.log('tmdb ep', ep);
                        console.log('source ep', episode);

                        let epMatch = ep.hash === episode.hash;

                        if (epMatch) {
                            return epMatch
                        }
                        console.group(episode.title);

                        let dateMatches = episode.title.match(/第? ?(\d{4})[\.\-]?(\d{2})[\.\-]?(\d{2}) ?[集期]?(.*)?$/);

                        let dateText = null;
                        let date = null;
                        let otherText = null;

                        if (dateMatches) {
                            dateText = dateMatches[0];
                            date = dateMatches.slice(1, 4).join('-');
                            otherText = dateMatches[4];

                            console.log('dateMatches', dateMatches);
                            console.log('date', date);
                            console.log('otherText', otherText);

                            epMatch = ep.air_date === date;
                            console.log('日期相等', epMatch);

                            if (otherText) {
                                epMatch = ep.title.includes(otherText);
                                console.log('包含文字', ep.title, otherText, epMatch);
                            }



                            return epMatch;
                        }

                        if (!epMatch) {
                            epMatch = ep.title.includes(episode.title)
                        }

                        console.groupEnd();

                        return epMatch;
                    });

                    episode = {
                        ...episode, ...episodeData
                    }
                    return episode;
                });

            }

            console.log('合并后的episdoes', episodes);

            episodeList.setEpisodes(episodes);
            this.playSourceEpisode(source, episodeList.currentEpisode ?? source.episodes[0]);
        });

        episodeList.addEventListener('episode_change', (event) => {
            const episode = event.detail.episode;
            this.playSourceEpisode(sourceList.currentSource, episode);
        });

        sourceList.setSources(sources);

        let urls = sources.map(source => source.episodes[0].url)

        SourceProviderChecker.checkM3u8UrlsWithCallbacks(urls, (firstResult, index) => {
            console.log('firstResult', sources[index]);

            sourceList.selectSource(sources[index]);
        }, (allResults) => {

            let speedTestResults = allResults.map((result, index) => {
                return [sources[index].provider_id, result.fragLoadedTime];
            })

            sources = this.updateSourcesBySpeed(sources, speedTestResults);

            sourceList.setSources(sources);

            if (!sourceList.currentSource) {
                sourceList.selectSource(sources[0]);
            }

            console.log('allResults', sources);

        });


        console.log(sources);

        return;

        this.watchRecord = await this.getUserWatchRecord();

        if (this.watchRecord) {
            let watchRecord = this.watchRecord;

            if (watchRecord.intro_duration) {
                this.introDuration = watchRecord.intro_duration;
            }

            this.video.currentTime = watchRecord.last_video_duration ?? watchRecord.intro_duration ?? 0;

            if (watchRecord.episodeIndex)
                this.currentEpisodeIndex = watchRecord.episode_index;

            let source = this.getSourceByID(watchRecord.provider_id);

            if (source) {
                this.currentSource = source;
            }
        }

        console.log(this);




        // this.SwitchToFastestSource()
    }

    updateSourcesBySpeed(sources, speedTestResults) {
        sources = sources.map((source) => {
            let result = speedTestResults.find(result => result[0] == source.provider_id);

            source.loadingTime = result ? result[1] : 0;

            return source;
        })

        sources.sort((a, b) => {
            if (b.loadingTime == -1 || a.loadingTime == -1)
                return b.loadingTime - a.loadingTime;

            return a.loadingTime - b.loadingTime
        });

        return sources;
    }


    SwitchToFastestSource() {
        let sourceListEl = this.sourceListEl;

        let urls = this.sources.map(source => source.episodes[0].url)

        SourceProviderChecker.testHlsUrls(urls, {
            onFirstLoaded: (testResult, urlIndex) => {


                let source = this.sources[urlIndex];

                console.log('切换到最快的航线', source, testResult);


                this.playSourceEpisode(source, this.currentEpisodeIndex)
            }
        });
    }

    setupDb() {
        const db = new Dexie('wptv');
        db.version(2).stores({
            watch_records: '&entry_id, sp_id, ep_hash, last_watch_time, last_video_duration, intro_duration, outro_duration'
        });

        this.idb = db;
    }

    async updateUserWatchRecord(data) {
        this.idb.watch_records.update(this.watchRecord.entry_id, data);
    }

    async getUserWatchRecord() {
        const db = this.idb;

        let record = await db.watch_records.get(this.postId);

        if (!record) {
            let key = await db.watch_records.add({
                entry_id: this.postId,
            });

            record = await db.watch_records.get(key);
        }

        record = Object.assign({
            ep_hash: 0,
            last_watch_time: Date.now(),
            last_video_duration: 0,
            intro_duration: 0,
            outro_duration: 0,
            sp_id: 0,
        }, record);

        record = new WatchRecord(record)

        return record;
    }

    async markIntroDuration(introDuration) {
        this.updateUserWatchRecord({
            intro_duration: introDuration
        })

        this.introDuration = introDuration
    }

    getSourceByID(id) {
        return this.sources.find(source => source.provider_id == id);
    }

    async onBeforeunload() {
        this.updateUserWatchRecord({
            last_video_duration: this.video.currentTime,
            last_watch_time: Date.now()
        })
    }


    getSpeedTestResults() {
        let cookie = Cookies.get('speed_test_results');
        const results = cookie ? JSON.parse(cookie) : '';
        return results;
    }

    async fetchEpisodesData() {
        let postId = this.postId;

        let data = {
            action: 'get_episodes_data',
            post_id: postId
        }

        const response = await fetch(themeSettings.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(data)
        });

        return response.json()
    }

    async fetchSources() {
        let postId = this.postId;

        let data = {
            action: 'get_sources',
            post_id: postId
        }

        const response = await fetch(themeSettings.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(data)
        });

        const sources = await response.json()

        return sources;
    }

    async playSourceEpisode(source, episode) {
        let url = episode.url;

        this.playM3u8(url);

        this.updateUserWatchRecord({
            sp_id: source.provider_id,
            ep_hash: episode.hash,
        });
    }

    playM3u8(url) {
        this.html5(url);
    }

    plyr(url) {
        const video = this.video;

        const plyr = new Plyr(video);

        const hls = new Hls();
        hls.loadSource(url);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, function () {
            video.play();
        });
    }

    vjsplayer(url) {
        const video = this.video;

        var player = videojs(this.video, {
            controls: true,
            autoplay: true,
            preload: 'auto'
        });

        const hls = new Hls();
        hls.loadSource(url);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, function () {
            video.play();
        });
    }


    dplayer(url) {


        const container = document.getElementById('player');
        const dp = new DPlayer({
            container: container,
            screenshot: true,
            video: {
                url: url,
                type: 'customHls',
                customType: {
                    customHls: (video) => {
                        this.addHlsSupport(video, url);
                    }
                }
            },
            pluginOptions: {
                hls: {
                    // hls config
                },
            },
            contextmenu: [
                {
                    text: 'custom1',
                    link: 'https://github.com/DIYgod/DPlayer',
                },
                {
                    text: 'custom2',
                    click: (player) => {
                        console.log(player);
                    },
                },
            ],
        });


        dp.seek(this.video.currentTime);
        dp.play();

        new DPlayerExtend(dp)

        dp.on('intro_duration_change', duration => {
            console.log('intro_duration_change', duration)

            this.markIntroDuration(duration);
        })
        dp.on('outro_duration_change', duration => {
            console.log('outro_duration_change', duration)

            this.markIntroDuration(duration);
        })
    }

    html5(url) {
        const video = this.video;
        const m3u8Url = decodeURIComponent(url);

        video.hidden = false;
        video.volume = 1.0;
        video.muted = Cookies.get('video_muted');

        if (Hls.isSupported()) {


        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = m3u8Url;
        }

        this.addHlsSupport(video, url);
    }

    addHlsSupport(video, url) {
        const hls = new Hls();

        hls.loadSource(url);
        hls.attachMedia(video);

        let startTime = Date.now();

        // for (const [eventCode, eventName] of Object.entries(Hls.Events)) {
        //     hls.on(eventName, (event, data) => {
        //         const loadingTime = Date.now() - startTime;
        //         console.log(eventCode, data);
        //     })
        // }

        let slowInternetTimeout = null;

        let threshold = 3000; //ms after which user perceives buffering

        video.addEventListener('waiting', () => {
            console.log('Video is waiting for more data.')
            slowInternetTimeout = setTimeout(() => {
                console.log('侦测到缓慢');
            }, threshold);
        });
        video.addEventListener('playing', () => {
            if (slowInternetTimeout != null) {
                clearTimeout(slowInternetTimeout);
                slowInternetTimeout = null;
            }
        });

        hls.on(Hls.Events.MANIFEST_PARSED, () => {
            video.play();
        });

        hls.on(Hls.Events.ERROR, (event, data) => {
            console.log('ERROR', event, data, this);

            if (data.type == 'networkError') {
                this.currentSourceNetworkError = true;
            } else if (data.type == 'mediaError') {
                console.log('mediaError', data);

                if (data.details == 'bufferStalledError') {
                    console.log('缓冲区停滞错误');
                    this.showMessage('缓冲区停滞错误');
                }
            }
        });

        hls.on(Hls.Events.LEVEL_LOADED, (evet, data) => {
            const loadingTime = Date.now() - startTime;

            console.log('LEVEL_LOADED', loadingTime);
        })

        video.addEventListener('volumechange', (event, data) => {
            video.muted ? Cookies.set('video_muted', video.muted) : Cookies.remove('video_muted');
        })
    }

    showMessage(message, type = 'info') {
        this.messageEl.innerText = message;
        this.messageEl.dataset.type = type;
    }

    destroyVideo(video) {
        video.pause();
        video.src = "";
        video.load();
        video.remove();
    }
}

export default SourcesManager;