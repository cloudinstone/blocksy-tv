class DPlayerExtend {
    constructor(dp, options) {

        options = Object.assign({}, {
            introTime: null,
            introTimeLimit: 120, // todo
            outroTime: null,
            outroTimeLimit: 120, // todo
            onIntroTimeChange: (time) => { },
            onOutroTimeChange: (time) => { },
        }, options)

        for (const [key, value] of Object.entries(options)) {
            this[key] = value;
        }

        // Remove default contextmenu items.
        [...dp.template.menu.children].reverse().slice(0, 3).forEach(item => item.remove());
        !dp.template.menu.children.length && dp.template.menu.remove()

        dp.events.playerEvents.push('introtime_change');
        dp.events.playerEvents.push('outrotime_change');

        const originPanel = dp.template.settingBox.children[0];

        const settingItemSkipIntro = this.createSettingItem({
            classname: 'skip-intro',
            label: '跳过片头',
            time: this.introTime
        });

        console.log(dp);

        settingItemSkipIntro.addEventListener('click', (event) => {
            let time = dp.video.currentTime;
            if (this.introTimeLimit && time > this.introTimeLimit)
                time = this.introTimeLimit

            let hms = this.toHms(time);

            settingItemSkipIntro.querySelector('.dplayer-time').innerText = hms;

            dp.events.trigger('introtime_change', time);

            this.onIntroTimeChange(time)
        })

        originPanel.append(settingItemSkipIntro);

        const settingItemSkipOutro = this.createSettingItem({
            classname: 'skip-outro',
            label: '跳过片尾',
            time: this.outroTime
        })

        settingItemSkipOutro.addEventListener('click', (event) => {
            let time = dp.video.currentTime;
            if (this.outroTimeLimit && time < dp.video.duration - this.outroTimeLimit)
                time = dp.video.duration - this.outroTimeLimit

            let hms = this.toHms(time);

            settingItemSkipOutro.querySelector('.dplayer-time').innerText = hms;

            dp.events.trigger('outrotime_change', time);

            this.onOutroTimeChange(dp.time)
        })

        originPanel.append(settingItemSkipOutro);
    }

    createSettingItem(options) {
        options = Object.assign({}, options);

        let settingItem = document.createElement('div');
        settingItem.classList.add('dplayer-setting-item', 'dplayer-setting-' + options.classname);

        let label = document.createElement('span');
        label.classList.add('dplayer-label');
        label.innerText = options.label;

        settingItem.append(label)

        let timeWrap = document.createElement('div');
        timeWrap.classList.add('dplayer-time')

        if (options.time)
            timeWrap.innerText = options.time;

        settingItem.append(timeWrap)

        return settingItem;
    }

    toHms(s) {
        return new Date(s * 1000).toISOString().slice(s > 3600 ? 11 : 14, 19)
    }
}

export default DPlayerExtend;