import OffCanvasTabsPlugin from 'src/plugin/offcanvas-tabs/offcanvas-tabs.plugin';
import DomAccess from 'src/helper/dom-access.helper';
import PluginManager from 'src/plugin-system/plugin.manager';

// replace OffCanvasTab with accordion like handling
export default class ReplaceOffCanvasTabsPlugin extends OffCanvasTabsPlugin {

    init() {
        super.init();
        this.content = null;
        this.target = DomAccess.querySelector(document, '.nav-link[href="' + DomAccess.getAttribute(this.el, 'href') + '"]');
    }

    _onClickOffCanvasTab(event) {
        // if the current viewport is not allowed return
        if (this._isInAllowedViewports() === false) return;

        event.preventDefault();

        // close on 2nd click on same link
        if(this.target.classList.contains('open')) {
            this.close();
            return;
        }

        const tab = event.currentTarget;
        if (DomAccess.hasAttribute(tab, 'href')) {
            const tabTarget = DomAccess.getAttribute(tab, 'href');
            const pane = DomAccess.querySelector(document, tabTarget);
            PluginManager.getPluginInstances('OffCanvasTabs').forEach(plugin => {
                if (plugin.el !== this.el && plugin instanceof ReplaceOffCanvasTabsPlugin) {
                    plugin.close();
                }
            });

            if(typeof this.content !== 'object' || this.content === null) {
                this.content = document.createElement("div");
                this.content.classList.add('nav-item-direct-content');
                this.content.innerHTML = pane.innerHTML;
                this.target.after(this.content);
            }
            this.target.classList.add('open');
        }

        this.$emitter.publish('onClickOffCanvasTab');
    }

    close() {
        this.el.classList.remove('open');
    }
}
