const { Component, Mixin } = Shopware;
import template from './sw-cms-el-netzp-powerpack6-map.html.twig';
import './sw-cms-el-netzp-powerpack6-map.scss';

Component.register('sw-cms-el-netzp-powerpack6-map', {
    template,

    mixins: [
        Mixin.getByName('cms-element')
    ],

    inject: ['systemConfigApiService'],

    data() {
        return {
            config: {},
        };
    },

    created() {
        this.createdComponent();
    },

    computed: {
        getMapStyle() {
            return 'height: ' + this.element.config.height.value + 'rem';
        },

        getMapScript() {
            return `
            function initMap${this.element.id}() 
            {
                let map = new google.maps.Map(document.getElementById('netzp-powerpack6-map${this.element.id}'), 
                {
                    center: { lat: ${this.element.config.lat.value}, lng: ${this.element.config.long.value} },
                    zoom: ${this.element.config.zoomLevel.value},
                    mapTypeId: '${this.element.config.mapType.value}',
                    mapId: '${this.element.id}'
                });
                
                if('${this.element.config.contents.value}' != 'null' && '${this.element.config.contents.value}' != '') 
                {
                    let infowindow = new google.maps.InfoWindow({
                        content: '${this.element.config.contents.value}'
                    });
                    
                    let marker = new google.maps.marker.AdvancedMarkerElement({ 
                        position: { 
                            lat: ${this.element.config.lat.value}, 
                            lng: ${this.element.config.long.value} 
                        }, 
                        map: map
                    });
                    marker.addListener('click', function() {
                        infowindow.open(map, marker);
                    });
                }
            }
            `;
        }
    },

    methods: {
        createdComponent()
        {
            this.initElementConfig('netzp-powerpack6-map');
            this.addGoogleScript(this.element.id);
        },

        addGoogleScript(id)
        {
            this.systemConfigApiService
                .getValues('NetzpPowerPack6.config')
                .then(data => {
                    this.config = data;

                    const apiKey = this.config['NetzpPowerPack6.config.googleMapsApiKey'];
                    const scriptId = "netzp-powerpack6-google-map-script_" + id;

                    if(window.google && window.google.maps)
                    {
                        eval('initMap' + id + '();');
                    }
                    else if( ! document.getElementById(scriptId))
                    {
                        if((typeof google === 'object') && (typeof google.maps === 'object')) {
                            return;
                        }

                        let scriptTag = document.createElement("script");
                        scriptTag.id = scriptId;
                        scriptTag.src = "https://maps.googleapis.com/maps/api/js?libraries=core,maps,marker&key=" + apiKey + "&callback=initMap" + id;

                        let head = document.getElementsByTagName("head")[0];
                        head.appendChild(scriptTag);
                    }
                });
        }
    }
});
