class DPlayerExtend {
    constructor(dp, options) {

        options = Object.assign({}, {
            introDuration: null,
            maxIntroDuration: 120, // todo
            outroDuration: null,
            maxOutroDuration: 120, // todo
        }, options)

        for (const [key, value] of Object.entries(options)) {
            this[key] = value;
        }

        // Remove default contextmenu items.
        [...dp.template.menu.children].reverse().slice(0, 3).forEach(item => item.remove());
        !dp.template.menu.children.length && dp.template.menu.remove()

        dp.events.playerEvents.push('intro_duration_change');
        dp.events.playerEvents.push('outro_duration_change');

        const originPanel = dp.template.settingBox.children[0];

        const settingItemSkipIntro = this.createSettingItem({
            classname: 'skip-intro',
            label: '跳过片头',
            duration: this.introDuration
        });

        console.log(dp);

        settingItemSkipIntro.addEventListener('click', (event) => {
            let duration = dp.video.currentTime;
            if (this.maxIntroDuration && duration > this.maxIntroDuration)
                duration = this.maxIntroDuration

            let hms = this.toHms(duration);

            settingItemSkipIntro.querySelector('.dplayer-time').innerText = hms;

            dp.events.trigger('intro_duration_change', duration);
        })

        originPanel.append(settingItemSkipIntro);

        const settingItemSkipOutro = this.createSettingItem({
            classname: 'skip-outro',
            label: '跳过片尾',
            duration: this.outroDuration
        })

        settingItemSkipOutro.addEventListener('click', (event) => {
            let duration = dp.video.currentTime;
            if (this.maxOutroDuration && duration < dp.video.duration - this.maxOutroDuration)
                duration = dp.video.duration - this.maxOutroDuration

            let hms = this.toHms(duration);

            settingItemSkipOutro.querySelector('.dplayer-time').innerText = hms;

            dp.events.trigger('outro_duration_change', duration);
        })

        originPanel.append(settingItemSkipOutro);
    }

    createSettingItem(options) {
        const { classname, label, duration = '' } = options;

        let settingItem = document.createElement('div');
        settingItem.classList.add('dplayer-setting-item', 'dplayer-setting-' + classname);

        let labelEl = document.createElement('span');
        labelEl.classList.add('dplayer-label');
        labelEl.innerText = label;
        settingItem.append(labelEl)

        let durationEl = document.createElement('div');
        durationEl.classList.add('dplayer-time')
        durationEl.innerText = duration;
        settingItem.append(durationEl)

        return settingItem;
    }

    toHms(s) {
        return new Date(s * 1000).toISOString().slice(s > 3600 ? 11 : 14, 19)
    }
}

export default DPlayerExtend;