import Plugin from 'src/plugin-system/plugin.class';

export default class LoadMoreImagesPlugin extends Plugin {
    static options = {
        initialVisibleImages: 5,
        loadMoreButtonSelector: '.load-more-images-btn',
        imageItemSelector: '.gallery-slider-item',
        hiddenClass: 'd-none'
    };

    init() {
        this._registerEvents();
        this._hideExtraImages();
    }

    _registerEvents() {
        const loadMoreBtn = this.el.querySelector(this.options.loadMoreButtonSelector);
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', this._onLoadMoreClick.bind(this));
        }
    }

    _hideExtraImages() {
        const images = this.el.querySelectorAll(this.options.imageItemSelector);
        const loadMoreBtn = this.el.querySelector(this.options.loadMoreButtonSelector);
        
        if (images.length <= this.options.initialVisibleImages) {
            // Hide button if images are less than or equal to initial visible count
            if (loadMoreBtn) {
                loadMoreBtn.style.display = 'none';
            }
            return;
        }

        images.forEach((image, index) => {
            if (index >= this.options.initialVisibleImages) {
                image.classList.add(this.options.hiddenClass);
            }
        });
    }

    _onLoadMoreClick(event) {
        event.preventDefault();
        
        const hiddenImages = this.el.querySelectorAll(`.${this.options.imageItemSelector}.${this.options.hiddenClass}`);
        const loadMoreBtn = event.currentTarget;

        hiddenImages.forEach(image => {
            image.classList.remove(this.options.hiddenClass);
        });

        // Hide the load more button after showing all images
        loadMoreBtn.style.display = 'none';
    }
}
