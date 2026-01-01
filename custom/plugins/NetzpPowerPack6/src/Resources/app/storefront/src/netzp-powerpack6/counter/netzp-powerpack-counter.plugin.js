export default class NetzpPowerpack6Counter extends window.PluginBaseClass
{
    static options = {
        speed: 1000,
        id: ''
    };

    init()
    {
        let me = this,
            container = document.querySelector('[data-counter-' + this.options.id + '-container=true]'),
            template = container.getAttribute('data-text'),
            startValue = + container.getAttribute('data-start'),
            endValue = + container.getAttribute('data-end'),

            options = {
                rootMargin: '0px',
                threshold: 1.0
            },

            observer = new IntersectionObserver(function(entries, observer) {
                entries.forEach(entry => {
                    if(entry.isIntersecting) {
                        me.animate(entry.target, template, startValue, endValue, me.options.speed);
                    }
                });
            }, options);

        observer.observe(container);
    }

    animate(container, template, startValue, endValue, speed)
    {
        let startTime = null;
        let currentTime = Date.now();

        container.innerHTML = this.replaceTemplate(template, startValue, startValue, endValue);

        const step = (currentTime) =>
        {
            if ( ! startTime) {
                startTime = currentTime;
            }

            const progress = Math.min((currentTime  - startTime) / speed, 1);
            container.innerHTML = this.replaceTemplate(
                template,
                Math.floor(progress * (endValue - startValue) + startValue),
                startValue,
                endValue
            );

            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
            else {
                window.cancelAnimationFrame(window.requestAnimationFrame(step));
            }
        };

        window.requestAnimationFrame(step);
    }

    replaceTemplate(template, currentValue, startValue, endValue)
    {
        if(template === '') {
            template = '{counter}';
        }

        template = template.replace(/\{counter\}/g, currentValue);
        template = template.replace(/\{start\}/g, startValue);
        template = template.replace(/\{end\}/g, endValue);

        return template;
    }
}
