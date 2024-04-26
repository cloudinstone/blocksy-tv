import Cookies from 'js-cookie';
import Dexie from 'dexie';

class Source {
    video;
    postId = 0;
    sourceList = [];
    firstLoadedSource;
    currentSource;
    currentSourceNetworkError = false;
    currentEpisodeIndex = 0;
    idb;
    sourceListEl;
    episodeListEl;
    introEndTime = 0;
    noticeEl;

    constructor() {
        this.postId = parseInt(document.querySelector('.source-area').dataset.postId);
        this.video = document.getElementById("video");
        this.sourceListEl = document.querySelector('.source-list')
        this.episodeListEl = document.querySelector('.episode-list')
        this.noticeEl = document.querySelector('.source-notice')

        // // console.log('Source', this);

        const db = new Dexie('wptv');
        db.version(2).stores({
            watch_history_items: '++id, post_id, source_slug, episode_index, play_time, exit_time, intro_end_time'
        });

        // console.log(db);
        this.idb = db;


        document.querySelector('.select-season').addEventListener('change', (event) => {
            window.location.href = event.target.value;
        })



        document.querySelector('.mark-intro-end-time').addEventListener('click', () => {

            this.markIntroEndTime(this.video.currentTime)
        });

        this.syncIntroEndTime();
    }

    init() {
        this.load();
    }

    async syncIntroEndTime() {
        const db = this.idb;
        const historyItem = await db.watch_history_items.where({ post_id: this.postId }).first();

        if (historyItem) {
            this.introEndTime = historyItem.intro_end_time;
        }
    }

    async markIntroEndTime(introEndTime) {

        const db = this.idb;
        const historyItem = await db.watch_history_items.where({ post_id: this.postId }).first();

        console.log('markIntroEndTime', historyItem);

        if (historyItem) {
            db.watch_history_items.update(historyItem.id, {
                intro_end_time: introEndTime
            })

            this.introEndTime = introEndTime
        }
    }

    async load() {
        this.sourceList = await this.fetchSourceList();

        console.log('fetchSourceList', this.sourceList);



        const db = this.idb;
        const historyItems = await db.watch_history_items
            .where({
                post_id: this.postId
            })
            .toArray();

        console.log('加载前查询用户的History Items', historyItems);

        if (historyItems.length) {
            let historyItem = historyItems[0];

            this.currentEpisodeIndex = historyItem.episode_index;

            let source = this.getSourceBySlug(historyItem.source_slug);

            if (source) {
                this.currentSource = source;
                this.renderSourceList();
                this.renderEpisodeListBySource(this.currentSource)
                this.playSourceEpisode(this.currentSource, this.currentEpisodeIndex);

                this.testAllSources();
            }

            this.video.currentTime = historyItem.exit_time;

        } else {
            this.renderSourceList();
            this.testAllSources();
        }



        window.addEventListener("beforeunload", (e) => {
            // console.log('beforeunload')

            this.onBeforeunload();
        });



    }

    getSourceBySlug(slug) {
        return this.sourceList.find(source => source.provider_slug == slug);
    }

    async onBeforeunload() {
        const db = this.idb;

        const historyItems = await db.watch_history_items
            .where({
                post_id: this.postId
            })
            .toArray();

        if (historyItems.length) {
            let historyItem = historyItems[0];

            db.watch_history_items.update(historyItem.id, {
                exit_time: this.video.currentTime
            })
        }
    }

    testAllSources() {
        let sourceListEl = this.sourceListEl;

        this.sourceList.forEach((source, sourceIndex) => {
            let itemEl = sourceListEl.querySelector('li:nth-of-type(' + (sourceIndex + 1) + ')');

            let m3u8Url = source.srcset[0].url;

            let startTime = Date.now();

            let errorHandled = false;


            let video = document.createElement('video');
            video.volume = 0;
            let hls = new Hls();
            m3u8Url = decodeURIComponent(m3u8Url)
            hls.loadSource(m3u8Url);
            hls.attachMedia(video);

            const onComplete = () => {
                let allTestsCompleted = !this.sourceList.some(source => !source.loadingTime);



                if (allTestsCompleted) {
                    console.log('allTestsCompleted', allTestsCompleted);

                    if (this.currentSourceNetworkError) {
                        const loadingSortedSourceList = this.sourceList.sort((a, b) => a.loadingTime - b.loadingTime);
                        const fastestSource = loadingSortedSourceList.find(source => source.loadingTime > -1)

                        console.log('fastestSource', fastestSource);

                        this.playSourceEpisode(fastestSource, this.currentEpisodeIndex)

                        this.currentSourceNetworkError = false;
                    }

                    this.sourceList.sort((a, b) => {
                        if (a.loadingTime == -1)
                            a.loadingTime = Infinity;
                        if (b.loadingTime == -1)
                            b.loadingTime = Infinity;

                        // return b.srcset.length - a.srcset.length || a.loadingTime - b.loadingTime
                        return a.loadingTime - b.loadingTime
                    })

                    console.log('sortedSourceList', this.sourceList)
                }

                hls.destroy();
                this.destroyVideo(video);
            }

            hls.on(Hls.Events.ERROR, (event, data) => {
                // console.log('onError', source, event, data);

                if (errorHandled) {
                    return;
                }

                if (data.type == 'networkError') {
                    this.sourceList[sourceIndex].loadingTime = -1;

                    itemEl.querySelector('.speed').innerText = '-1ms';
                    itemEl.classList.add('speed-error');
                }

                onComplete()

                errorHandled = true;
            });

            hls.on(Hls.Events.MANIFEST_PARSED, (event, data) => {
                startTime = Date.now();
            });

            video.addEventListener('loadeddata', (event) => {
                const loadingTime = Date.now() - startTime;

                // console.log(source.provider_name, '✅ 加载时间', loadingTime, m3u8Url);


                if (!this.firstLoadedSource) {
                    this.firstLoadedSource = source;
                }

                if (!this.currentSource || this.currentSourceNetworkError) {
                    this.currentSource = source;

                    this.currentEpisodeIndex = 0;

                    this.renderEpisodeList(source.srcset);

                    itemEl.classList.add('active');

                    this.playSourceEpisode(source, 0);

                    this.currentSourceNetworkError = false;
                }

                this.sourceList[sourceIndex].loadingTime = loadingTime;

                itemEl.querySelector('.speed').innerText = loadingTime + 'ms';

                let speed = 'high';
                if (loadingTime > 4000) {
                    speed = 'low';
                } else if (loadingTime > 2000) {
                    speed = 'medium';
                }
                itemEl.classList.add('speed-' + speed);


                onComplete()
            }
            )
        });
    }

    onClickSourceItem(event, source) {
        console.log('点击资源', source, this)

        const sourceListEl = this.sourceListEl;

        let itemEl = event.target.closest('li')

        if (itemEl.classList.contains('active')) {
            sourceListEl.classList.toggle('open');

            return false;
        }

        // update view
        let prevActive = sourceListEl.querySelector('.active');
        prevActive && prevActive.classList.remove('active')
        itemEl.classList.add('active');
        sourceListEl.classList.remove('open');

        this.renderEpisodeList(source.srcset);


        this.playSourceEpisode(source, this.currentEpisodeIndex);
    }

    renderSourceList() {
        let sourceListEl = this.sourceListEl;

        this.sourceList.forEach((source, sourceIndex) => {
            let itemClone = document.querySelector('#source-list-item').content.cloneNode(true);
            let itemEl = itemClone.querySelector('li')

            itemEl.querySelector('.name').innerText = source.provider_name;
            itemEl.querySelector('.episode-count').innerText = source.srcset.length;

            if (this.currentSource && source.provider_slug == this.currentSource.provider_slug) {
                itemEl.classList.add('active');
            }

            itemEl.addEventListener('click', (event) => this.onClickSourceItem(event, source))

            sourceListEl.append(itemClone)
        })
    }


    onClickEpisodeItem(event) {
        // console.log('onClickEpisodeItem', this);

        let episodeListEl = this.episodeListEl;

        let episodeItem = event.target.closest('li');

        let episodeIndex = Array.prototype.indexOf.call(episodeItem.parentNode.children, episodeItem)

        if (episodeItem.classList.contains('active'))
            return false;

        let prevActive = episodeListEl.querySelector('.active');
        prevActive && prevActive.classList.remove('active')
        episodeItem.classList.add('active')

        // this.playM3u8(url)



        this.playSourceEpisode(this.currentSource, episodeIndex);
        this.video.currentTime = this.introEndTime;

        console.log('点击分集', episodeIndex, this.currentSource.srcset[episodeIndex]);
    }

    renderEpisodeListBySource(source) {
        const srcset = source.srcset;
        this.renderEpisodeList(srcset)
    }

    renderEpisodeList(srcset) {
        let episodeList = document.querySelector('.episode-list');

        episodeList.innerHTML = '';

        // console.log('srcset', srcset);

        srcset.forEach((src, episodeIndex) => {
            let episodeClone = document.querySelector('#episode-list-item').content.cloneNode(true);
            let episodeItem = episodeClone.querySelector('li');

            episodeItem.querySelector('.name').innerText = src.label;

            if (episodeIndex == this.currentEpisodeIndex) {
                episodeItem.classList.add('active')
            }

            episodeList.append(episodeItem);

            episodeItem.addEventListener('click', e => this.onClickEpisodeItem(e))
        })
    }

    async fetchSourceList() {
        let postId = this.postId;

        let data = {
            action: 'get_vod_source_list',
            post_id: postId
        }

        const response = await fetch(themeSettings.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(data)
        });

        const sourceList = await response.json()

        return sourceList;
    }

    async playSourceEpisode(source, episodeIndex) {
        let url = source.srcset[episodeIndex].url;

        this.playM3u8(url);

        this.currentSource = source;
        this.currentEpisodeIndex = episodeIndex;

        /**
         * Update Watch History
         */
        const db = this.idb;

        const historyItems = await db.watch_history_items
            .where({
                post_id: this.postId
            })
            .toArray();

        // console.log('historyItems', historyItems);

        if (historyItems.length) {
            db.watch_history_items.update(historyItems[0].id, {
                source_slug: source.provider_slug,
                episode_index: episodeIndex,
                play_time: Date.now()
            });
        } else {
            db.watch_history_items.add({
                post_id: this.postId,
                source_slug: source.provider_slug,
                episode_index: episodeIndex,
                play_time: Date.now()
            });
        }
    }


    playM3u8(url) {
        const video = this.video;
        const m3u8Url = decodeURIComponent(url);

        video.hidden = false;
        video.volume = 1.0;
        video.muted = Cookies.get('video_muted');

        if (Hls.isSupported()) {
            const hls = new Hls();

            hls.loadSource(m3u8Url);
            hls.attachMedia(video);
            hls.on(Hls.Events.MANIFEST_PARSED, () => {
                video.play();
            });



            hls.on(Hls.Events.ERROR, (event, data) => {
                console.log('ERROR', event, data, this);

                if (data.type == 'networkError') {
                    this.currentSourceNetworkError = true;

                    if (firstLoadedSource) {
                        this.playSourceEpisode(this.firstLoadedSource, this.currentEpisodeIndex);
                        this.currentSourceNetworkError = false;
                    }
                } else if (data.details == '"bufferStalledError"') {
                    this.showNotice('缓冲区停滞错误');
                }

            });

            video.addEventListener('volumechange', (event) => {
                Cookies.set('video_muted', video.muted);
            })


        } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = m3u8Url;
        }
    }

    showNotice(message, type = 'info') {
        this.noticeEl.innerText = message;
        this.noticeEl.dataset.type = type;
    }

    destroyVideo(video) {
        video.pause();
        video.src = "";
        video.load();
        video.remove();
    }
}

export default Source;