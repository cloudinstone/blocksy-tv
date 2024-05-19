class SourceProviderChecker {
    constructor() {

    }
    static async checkAllProviders() {
        let data = {
            action: 'get_source_providers'
        }

        let sourceProviders = await fetch(themeSettings.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams(data)
        }).then(response => response.json());

        console.log('sourceProviders', sourceProviders);

        sourceProviders.forEach(provider => {
            SourceProviderChecker.checkProvider(provider);
        });
    }

    static async checkProvider(provider) {
        let apiUrl = provider.api_url;
        let url = new URL(apiUrl);
        url.searchParams.append('ac', 'detail');

        let restUrl = 'https://hdzy.local/wp-json/wptv/v1/view_json?url=' + encodeURIComponent(url.href);

        let data = await fetch(restUrl).then(response => response.json());

        let srcset = data.list[0]['vod_play_url'];

        let srclist = SourceProviderChecker.parseSrcset(srcset);
        let firstUrl = srclist[0]['url'];

        let checkUrlResult = await SourceProviderChecker.checkM3u8Url(firstUrl);

        console.group(provider.name);
        console.log('checkUrlResult', checkUrlResult);

        if (checkUrlResult.fragUrl) {
            let fragHost = new URL(checkUrlResult.fragUrl).hostname;
            let hostData = SourceProviderChecker.getHostData(fragHost);
            // console.log('hostData', hostData);
        }

        console.groupEnd();
    }

    static async checkM3u8UrlsWithCallbacks(urls, onFirstSuccessCallback, onAllCompletedCallback) {
        let promises = urls.map(url => SourceProviderChecker.checkM3u8Url(url));

        let allResultsPromise = Promise.all(promises);

        let firstResultPromise = new Promise((resolve, reject) => {
            let firstResolved = false;
            for (let i = 0; i < promises.length; i++) {
                promises[i].then(result => {
                    if (!firstResolved && result.fragLoadedTime !== -1) {
                        firstResolved = true;
                        onFirstSuccessCallback(result, i);
                        resolve(result);
                    }
                }).catch(error => {
                    // Handle errors if needed
                });
            }
        });

        try {
            let [firstResult, allResults] = await Promise.all([firstResultPromise, allResultsPromise]);
            onAllCompletedCallback(allResults);
        } catch (error) {
            console.error("Error:", error);
            // Handle errors if needed
        }
    }

    static async checkM3u8Urls(urls) {
        return await Promise.all(urls.map(async url => {
            try {
                let data = await SourceProviderChecker.checkM3u8Url(url);
                console.log(data);
                return data;
            } catch (error) {
                console.error(error);
                return null;
            }
        }));
    }

    static async checkM3u8Url(m3u8Url) {
        return new Promise((resolve, reject) => {
            const video = document.createElement('video');
            video.muted = true;

            if (Hls.isSupported()) { }

            const hls = new Hls({
                manifestLoadPolicy: {
                    default: {
                        maxLoadTimeMs: 3000,
                        timeoutRetry: {
                            maxNumRetry: 0
                        },
                        errorRetry: {
                            maxNumRetry: 0
                        },
                    },
                },
                fragLoadPolicy: {
                    default: {
                        maxLoadTimeMs: 3000,
                        timeoutRetry: {
                            maxNumRetry: 0
                        },
                        errorRetry: {
                            maxNumRetry: 0
                        },
                    },
                },
            });
            hls.loadSource(m3u8Url);
            hls.attachMedia(video);

            let loadingStartTime;
            let manifestParsedTime;

            const destroyVideoAndHLS = () => {
                hls.destroy();
                video.parentNode.removeChild(video);
            }

            const handleManifestParsed = () => {
                manifestParsedTime = performance.now();
                hls.off(Hls.Events.MANIFEST_PARSED, handleManifestParsed);
                hls.on(Hls.Events.FRAG_LOADED, handleFragLoaded);
            }

            const handleFragLoaded = (event, data) => {
                let fragLoadedTime = data.frag.stats.loading.end - data.frag.stats.loading.start;

                // console.log(data);

                hls.off(Hls.Events.FRAG_LOADED, handleFragLoaded);

                let fragUrl = data.frag._url;

                resolve({
                    url: m3u8Url,
                    manifestParsedTime,
                    fragLoadedTime,
                    fragUrl
                });

                destroyVideoAndHLS();
            }

            hls.on(Hls.Events.MEDIA_ATTACHED, () => {
                loadingStartTime = performance.now();

                hls.on(Hls.Events.MANIFEST_PARSED, handleManifestParsed);
            });

            hls.on(Hls.Events.ERROR, (event, data) => {
                resolve({
                    url: m3u8Url,
                    manifestParsedTime: -1,
                    fragLoadedTime: -1
                });
            });

            video.addEventListener('error', (event) => {
                reject(event);
            });

            video.addEventListener('ended', () => {
                console.log('Video playback ended before testing completed.');
                destroyVideoAndHLS();
                reject('Video playback ended before testing completed.');
            });

        });
    }

    static async getHostData(host) {
        return await fetch('http://ip-api.com/json/' + host).then(response => response.json());
    }

    static parseSrcset(srcset) {
        let srclist = [];
        let srclines = srcset.split('#');
        srclines.forEach(line => {
            let pair = line.split('$');
            srclist.push({
                label: pair[0],
                url: pair[1]
            });
        });

        return srclist;
    }

    static destroyVideo(video) {
        video.pause();
        video.src = "";
        video.load();
        video.remove();
    }
}

export default SourceProviderChecker;