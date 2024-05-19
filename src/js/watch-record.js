class WatchRecord {
    entry_id = 0;
    sp_id = 0;
    ep_index = 0;
    last_watch_time = null;
    last_video_duration = 0;
    intro_durtion = 0;
    outro_durtion = 0;

    constructor(record) {
        for (const key in record) {
            if (this.hasOwnProperty(key)) {
                this[key] = record[key]
            }
        }
    }
}

export default WatchRecord;