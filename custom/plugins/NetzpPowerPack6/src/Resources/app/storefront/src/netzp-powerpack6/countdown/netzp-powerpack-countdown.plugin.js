export default class NetzpPowerpack6Countdown extends window.PluginBaseClass
{
    static options = {
        enddate: '',
        layout: 'text',
        countdown: '',
        elapsed: '',
        elapsedHide: '',
        id: ''
    };
    static timer = null;

    init() {
        const divCountdown = document.querySelector('[data-countdown-' + this.options.id + '-container=true]');
        const endDate = this.options.enddate.replace('+00:00', '');

        this.updateText(endDate, divCountdown);
        this.startTimer(endDate, divCountdown);
    }

    // thx to https://www.sitepoint.com/build-javascript-countdown-timer-no-dependencies/
    getTimeRemaining(endtime) {
        var t = Date.parse(endtime) - Date.parse(new Date());
        if(isNaN(t)) {
            t = 0
        }
        var seconds = Math.floor( (t / 1000) % 60 );
        var minutes = Math.floor( (t / 1000 / 60) % 60 );
        var hours = Math.floor( (t / (1000 * 60 * 60)) % 24 );
        var days = Math.floor( t / (1000 * 60 * 60 * 24) );

        return {
            'total': t,
            'days': days,
            'hours': hours,
            'minutes': minutes,
            'seconds': seconds
        };
    }

    updateText(endtime, container) {
        var c = this.getTimeRemaining(endtime);
        var s = '';

        if(c.total <= 0) {
            clearInterval(this.timer);
            if(this.options.elapsedHide == '1') {
                // remove whole cms block
                container.parentNode.parentNode.parentNode.parentNode.parentNode.remove();
            }
            else {
                container.textContent = this.options.elapsed;
            }
        }
        else if(this.options.layout == 'boxes') {
            const box1 = container.querySelector('[class="box1"] .counter');
            const box2 = container.querySelector('[class="box2"] .counter');
            const box3 = container.querySelector('[class="box3"] .counter');
            const box4 = container.querySelector('[class="box4"] .counter');
            box1.textContent = c.days;
            box2.textContent = c.hours;
            box3.textContent = c.minutes;
            box4.textContent = c.seconds;
        }
        else {
            s = this.options.countdown;
            s = s.replace('{days}', c.days);
            s = s.replace('{hours}', c.hours);
            s = s.replace('{minutes}', c.minutes);
            s = s.replace('{seconds}', c.seconds);
            container.textContent = s;
        }
    }

    startTimer(endtime, container) {
        var me = this
        me.timer = setInterval(function () {
            me.updateText(endtime, container)
        }, 1000);
    }
}
