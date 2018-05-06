import { Component, HostListener, OnInit } from '@angular/core'
import { GlobalVars } from './../../../../env'
import { HttpService, SearchService, HelperService } from './../../../services'

@Component({
    selector: 'app-lightbox',
    templateUrl: './lightbox.component.html'
})
export class LightboxComponent implements OnInit {
    @HostListener('document:keydown', ['$event']) keypress(event) {
        // 37 = left
        // 39 = right
        // 27 = escape
        // 73 = i (info)

        switch (event.keyCode) {
            case 37:
                this.eLightboxNav(-1)
                break;
            case 39:
                this.eLightboxNav(1)
                break;
            case 27:
                this.eCloseLightbox()
                break;
            case 73:
                this.eToggleFileInfo()
                break;
        }
    }

    private bShowingInfo: boolean = false
    private bLoadingInfo: boolean = false

    private jFileInfo: any = null

    constructor(
        private searchService: SearchService,
        private httpService: HttpService,
        private helperService: HelperService,
        private gbl: GlobalVars
    ) { }

    ngOnInit() {
        this.searchService.eeThumbClick.subscribe(iIndex => {
            // lightbox just opened
            // do lightbox openeing stuff
            this.lightBoxFileSet()
        })
    }

    eCloseLightbox() {
        this.searchService.eeLightboxClose.emit()
        this.searchService.iActiveThumb = -1
    }

    eToggleFileInfo() {
        this.bShowingInfo = !this.bShowingInfo

        if (this.bShowingInfo) {
            this.getFileInfo()
        } else {
            this.bLoadingInfo = false
            // cancel any xhr
            this.jFileInfo = null
        }
    }

    getFileInfo() {
        // opening the info, load it
        this.bLoadingInfo = true
        // request info for right hand side
        
        this.httpService.getFileInfo(this.searchService.mData.search.results[this.searchService.iActiveThumb].id).subscribe(
            (data) => {
                this.bLoadingInfo = false
                this.jFileInfo = data['file']
            },
            (err) => {
                this.bLoadingInfo = false
                this.jFileInfo = null
            }
        )
    }

    eLightboxNav(iDelta) {
        if(this.searchService.iActiveThumb !== -1)
		{
            let iNewIndex = this.searchService.iActiveThumb + iDelta

			// wrap?
            if(iNewIndex >= this.searchService.mData.search.results.length)
			{
				iNewIndex = 0
			}
			if(iNewIndex < 0)
			{
				iNewIndex = this.searchService.mData.search.results.length - 1
			}
            this.searchService.iActiveThumb = iNewIndex

            this.lightBoxFileSet()
		}
    }

    lightBoxFileSet() {
        if(this.bShowingInfo){
            this.getFileInfo()
        }

        // preload neighbours, 2 after, 2 before
        this.httpService.preloadNeighboursToLightIndex()
    }

    tagClicked() {
        // close the lightbox
        this.eCloseLightbox()
    }
}
