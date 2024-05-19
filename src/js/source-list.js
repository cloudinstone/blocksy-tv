class SourceList extends HTMLElement {
    constructor() {
        super();
        this.sources = [];
        this.currentSource = null;
    }

    connectedCallback() {
        if (this.sources.length > 1)
            this.render();

        this.addEventListener('click', this.handleSourceClick.bind(this));
        this.addEventListener('sources_updated', () => this.render());
    }

    setSources(sources) {
        this.sources = sources;

        const event = new CustomEvent('sources_updated', { detail: { sources: this.sources } });
        this.dispatchEvent(event);
    }

    selectSource(source) {
        if (typeof source !== 'object') {
            source = this.sources.find(s => s.id === parseInt(source));
        }

        if (source) {
            this.currentSource = source;

            const event = new CustomEvent('source_change', { detail: { source } });
            this.dispatchEvent(event);
        } else {
            console.error(`Source with ID ${sourceId} not found.`);
        }
    }

    handleSourceClick(event) {
        const item = event.target.closest('li');

        if (item.classList.contains('active')) {
            return;
        }

        this.querySelector('.active')?.classList.remove('active');
        item.classList.add('active');

        const sourceId = event.target.closest('li').dataset.sourceId;

        if (sourceId) {
            this.selectSource(sourceId);
        }
    }

    render(sources = this.sources) {
        this.innerHTML = '';
        sources.forEach(source => {
            const listItem = document.createElement('li');
            listItem.dataset.sourceId = source.id;

            const isActive = source.id === (this.currentSource ? this.currentSource.id : null);
            if (isActive) {
                listItem.classList.add('active');
            }

            let speedText = '';
            let loadingTime = source.loadingTime;
            if (loadingTime !== undefined) {
                loadingTime = parseInt(loadingTime);
                speedText = this.getSpeedText(loadingTime);
                listItem.dataset.speed = this.getSpeedType(loadingTime);
            }

            listItem.innerHTML = `
                <span class="source-name">${source.provider_name}</span>
                <span class="episode-count">${source.episodes.length}</span>
                <span class="speed">${speedText}</span>
        `;
            this.appendChild(listItem);
        });
    }

    getSpeedText(loadingTime) {
        if (loadingTime === -1) {
            return '-1 ms';
        } else {
            return loadingTime + ' ms';
        }
    }

    getSpeedType(loadingTime) {
        if (loadingTime === -1) {
            return 'error';
        } else if (loadingTime > 4000) {
            return 'low';
        } else if (loadingTime > 2000) {
            return 'medium';
        } else {
            return 'high';
        }
    }
}

customElements.define('source-list', SourceList);

export default SourceList;
