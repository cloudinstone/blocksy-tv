class EpisodeList extends HTMLElement {
    constructor() {
        super();
        this.episodes = [];
        this.currentEpisode = null;
        this._itemType = ''; // thumbnail
    }

    connectedCallback() {
        this.render();
        this.addEventListener('click', this.handleEpisodeClick.bind(this));
        this.addEventListener('episodes_updated', () => this.render());
    }

    setEpisodes(episodes) {
        this.episodes = episodes;
        this.dispatchEpisodesUpdated(this.episodes);
    }

    selectEpisode(hash) {
        const episode = this.episodes.find(episode => episode.hash === hash);

        if (episode) {
            this.currentEpisode = episode;
            this.dispatchEpisodeChange(episode);
        } else {
            console.error(`Episode with ID ${hash} not found.`);
        }
    }

    handleEpisodeClick(event) {
        const item = event.target.closest('li');

        if (item.classList.contains('active')) {
            return;
        }

        this.querySelector('.active')?.classList.remove('active');
        item.classList.add('active');

        const hash = item.dataset.hash;

        this.selectEpisode(hash);
    }

    render(episodes = this.episodes) {
        if (this.episodes.length < 2) {
            return;
        }

        this.innerHTML = '';
        episodes.forEach((episode, index) => {
            episode = Object.assign({
                title: '',
                runtime: '',
                air_date: '',
                image: '',
            }, episode);

            const listItem = document.createElement('li');
            listItem.dataset.hash = episode.hash;


            const isActive = episode.hash === (this.currentEpisode ? this.currentEpisode.hash : null);
            if (isActive) {
                listItem.classList.add('active');
            }

            if (this.getAttribute('item-type') == 'thumbnail') {
                let thumbnailUrl = episode.image ? 'https://media.themoviedb.org/t/p/w227_and_h127_bestv2' + episode.image : '';

                listItem.innerHTML = `<div class="media">
        <img src="${thumbnailUrl}" alt="${episode.title}">
    </div>

    <div class="data">
    <h4><i>${episode.number}</i>. ${episode.title}</h4>

    <div clss="meta">
        <span class="runtime">${episode.runtime}</span>
        <span class="air-date">${episode.air_date}</span>
    </div>
    </div>`;
            } else {
                listItem.textContent = `${episode.title}`;
            }


            this.appendChild(listItem);
        });
    }

    dispatchEpisodeChange(episode) {
        const event = new CustomEvent('episode_change', { detail: { episode } });
        this.dispatchEvent(event);
    }

    dispatchEpisodesUpdated(episodes) {
        const event = new CustomEvent('episodes_updated', { detail: { episodes } });
        this.dispatchEvent(event);
    }
}

customElements.define('episode-list', EpisodeList);

export default EpisodeList;