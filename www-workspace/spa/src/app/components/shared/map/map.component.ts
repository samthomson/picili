import {
    Component,
    ViewChild,
    ElementRef,
    OnInit,
    Output,
    EventEmitter,
    AfterViewInit
} from '@angular/core';
import { HttpService, SearchService } from './../../../services';

declare var google;

@Component({
  selector: 'app-map',
  templateUrl: './map.component.html'
})

export class MapComponent implements OnInit {


    lat: number = 36.2048;
    lng: number = 138.25;

    // 36.2048° N, 138.2529°

    @Output() boundsChanged = new EventEmitter();
    @Output() idle = new EventEmitter();

    @ViewChild('map') mapElement: ElementRef;
    map: any;
    mapInitialised: boolean = false;
    apiKey: string = 'AIzaSyDR4kOXozjam-Y3xaMxq9mSABoJxHzsXhM';

    mapupdater = null;
    bounds: any;

    constructor(
        private searchService: SearchService
    ) { }

    ngOnInit() {
        this.loadGoogleMaps();
    }

    ngAfterViewInit() {
        // this.loadGoogleMaps();
    }

    initMap() {
        this.mapInitialised = true;

        let latLng = new google.maps.LatLng(this.lat, this.lng);
        let mapOptions = {
            center: latLng,
            zoom: 6,
            mapTypeId: google.maps.MapTypeId.ROADMAP
        };

        this.map = new google.maps.Map(this.mapElement.nativeElement, mapOptions);


        //
        // bounds changed event registration
        //
        google.maps.event.addListener(this.map, 'bounds_changed', () => {

            clearTimeout(this.mapupdater);

            this.mapupdater = setTimeout(() => {
                let mBounds = this.map.getBounds();

                this.bounds = {
                    northEast: {
                        lat: mBounds.getNorthEast().lat(),
                        lng: mBounds.getNorthEast().lng()
                    },
                    southWest: {
                        lat: mBounds.getSouthWest().lat(),
                        lng: mBounds.getSouthWest().lng()
                    }
                };

                this.boundsChanged.emit({
                    value: this.bounds
                });

            }, 500);

        });

        //
        // map idle event registration
        //
        /*
        16.3.16 why do we have this event?*/
        google.maps.event.addListener(this.map, 'idle', () => {

            let mBounds = this.map.getBounds();

            this.bounds = {
                northEast: {
                    lat: mBounds.getNorthEast().lat(),
                    lng: mBounds.getNorthEast().lng()
                },
                southWest: {
                    lat: mBounds.getSouthWest().lat(),
                    lng: mBounds.getSouthWest().lng()
                }
            };
            this.idle.emit({
                value: this.bounds
            });
        });

    }

    loadGoogleMaps()
    {
        if(typeof google == "undefined" || typeof google.maps == "undefined")
        {
            //Load the SDK
            window['mapInit'] = () => {
                this.initMap();
            }

            let script = document.createElement("script");
            script.id = "googleMaps";

            if(this.apiKey){

                script.src = 'https://maps.googleapis.com/maps/api/js?key=' + this.apiKey + '&callback=mapInit';
            } else {
                script.src = 'http://maps.google.com/maps/api/js?callback=mapInit';
            }

            document.body.appendChild(script);

        } else {
            this.initMap();
        }
    }

    resultThumbClick()
    {
        // this.searchService.iActiveThumb = i;
    }

}
