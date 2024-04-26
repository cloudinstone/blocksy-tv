class Scroll {
    constructor(list) {
        this.list = list;

        this.prev = null;
        this.next = null;

        this.isMounted = false;
    }

    init() {
        const resizeObserver = new ResizeObserver(() => this.update());
        resizeObserver.observe(this.list);
    }

    update() {
        this.mount();

        if (this.isMounted) {
            const list = this.list;

            this.prev.disabled = list.scrollLeft === 0;
            this.next.disabled = list.scrollLeft + list.offsetWidth === list.scrollWidth;
        }
    }

    mount() {
        if (this.isMounted)
            return;

        const list = this.list;

        if (list.offsetWidth === list.scrollWidth)
            return;

        if (!this.next) {
            this.next = this.createButton("\u02C3", 'next');
            this.list.after(this.next);
        }

        if (!this.prev) {
            this.prev = this.createButton("\u02C2", 'prev');
            this.list.after(this.prev);
        }

        list.addEventListener("scroll", this.update.bind(this));

        this.next.addEventListener("click", this.onClickNext.bind(this));

        this.prev.addEventListener("click", this.onClickPrev.bind(this));

        this.isMounted = true;
    }

    onClickNext() {
        const list = this.list;
        const items = list.childNodes;

        for (let item of items) {
            if (
                item.offsetLeft + item.offsetWidth >
                list.scrollLeft + list.offsetWidth
            ) {
                list.scrollTo({
                    top: 0,
                    left: item.offsetLeft,
                    behavior: "smooth"
                });
                break;
            }
        }
    }

    onClickPrev() {
        const list = this.list;
        const items = list.childNodes;

        for (let item of Array.from(items).reverse()) {
            if (item.offsetLeft <= list.scrollLeft) {
                list.scrollTo({
                    top: 0,
                    left: item.offsetLeft + item.offsetWidth - list.offsetWidth,
                    behavior: "smooth"
                });
                break;
            }
        }
    }

    createButton(text, className) {
        const button = document.createElement("button");
        button.type = "button";
        button.textContent = text;
        button.className = className;
        button.disabled = true;

        return button;
    }
}

export default Scroll;