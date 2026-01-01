import { Loader } from "@googlemaps/js-api-loader"

export default class NetzpPowerpack6Map extends window.PluginBaseClass
{
    static options = {
        el: '',
        optInPolicyClass: '',
        apiKey: '',
        lang: '',
        optIn: '',
        lat: '',
        lng: '',
        zoom: '',
        mapType: '',
        content: ''
    };

    subscribeToEvents()
    {
        document.$emitter.subscribe('netzp-pp-map-optin', (event) =>
        {
            if(event.detail.confirm === true)
            {
                localStorage.setItem('netzpGoogleMapsOptInAll', 1);
            }
            else
            {
                localStorage.setItem('netzpGoogleMapsOptIn_' + event.detail.confirm, 1);
            }

            this.showMap();
        });
    }

    showMap()
    {
        const loader = new Loader({
            apiKey: this.options.apiKey,
            version: "weekly"
        });

        const needsOptIn = parseInt(this.options.optIn) == 1;

        if(needsOptIn)
        {
            if (! parseInt(localStorage.getItem('netzpGoogleMapsOptInAll')) == 1 &&
                ! parseInt(localStorage.getItem('netzpGoogleMapsOptIn_' + this.options.el)) == 1)
            {
                return;
            }
        }

        const optInBanner = document.querySelector('#' + this.options.el + ' .' + this.options.optInPolicyClass);

        loader.load().then(async () =>
        {
            const { Map } = await google.maps.importLibrary('maps');
            const { AdvancedMarkerElement } = await google.maps.importLibrary('marker');

            const map = new Map(document.getElementById(this.options.el), {
                center: {
                    lat: parseFloat(this.options.lat),
                    lng: parseFloat(this.options.lng)
                },
                zoom: parseInt(this.options.zoom),
                mapTypeId: this.options.mapType,
                mapId: this.options.el
            });

            if(this.options.content != '')
            {
                const infoWindow = new google.maps.InfoWindow({
                    content: this.options.content
                });

                const marker = new AdvancedMarkerElement({
                    position: {
                        lat: parseFloat(this.options.lat),
                        lng: parseFloat(this.options.lng)
                    },
                    map: map,
                });
                marker.addListener('click', function() {
                    infoWindow.open(map, marker);
                });
            }
        });
    }

    init()
    {
        this.subscribeToEvents();
        this.showMap();
    }
}
