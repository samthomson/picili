import { HelperService } from './../../../../services/helper.service';
import {
	Component,
	OnInit,
	Input,
	ChangeDetectorRef,
	ElementRef,
	ViewChild,
	HostListener
} from '@angular/core';

import { HttpService, SearchService } from './../../../../services';

import { GlobalVars } from './../../../../../env';

@Component({
  selector: 'app-map-page',
  templateUrl: './map-page.component.html'
})
export class MapPageComponent implements OnInit {


	@Input() bounds: any;

	private zoom: number = 2;

	private aMapDots: any[] = [];
	private aMapIcons: any[] = [];

	private iMapWidth: string;
	private iResultsWidth: string;

	private bCurrentlyResizing: boolean = false;

	@ViewChild('bothContainersWidth') bothContainersWidth: ElementRef;

	resizeId;
	resizeSearchBlockId;

	@HostListener('window:resize')
	onWindowResize() {
		this.bCurrentlyResizing = true;
		// debounce resize, wait for resize to finish before doing stuff
		if (this.resizeId) {
			clearTimeout(this.resizeId);
		}
		if (this.resizeSearchBlockId) {
			clearTimeout(this.resizeSearchBlockId);
		}

		this.resizeId = setTimeout((() => {
			// wait 200 milliseconds (gbl.iResizeTimeout) before we resize containers, to be confident the resize has finished
			this.calculateContainerSizes()
			// then re-enable map searching
			this.resizeSearchBlockId = setTimeout((() => {
				this.bCurrentlyResizing = false
			}).bind(this), this.gbl.iResizeTimeout);
		}).bind(this), this.gbl.iResizeTimeout);
	}

	constructor(
		private ref: ChangeDetectorRef,
		private searchService: SearchService,
		private httpService: HttpService,
		private helperService: HelperService,
		private gbl: GlobalVars
	) {
		this.httpService.bSearchingChanged.subscribe(bSearching => {

			// only do this on map pan searches, not when loading more, as it will make the map icons flash off then on
			if (!this.searchService.bSearchingForMore) {

				this.aMapDots = [];
				this.aMapIcons = [];

				// if (bSearching) {
				//     // a search has begun.
				//     this.aMapDots = [];
				//     this.aMapIcons = [];
				// } else {
				if (!bSearching) {
					// a search has ended
					if (typeof this.searchService.mData !== 'undefined' && this.searchService.mData.search !== 'undefined') {
						if (this.searchService.mData.search.aggs) {
							if (this.searchService.mData.search.aggs.map_dots) {
								this.aMapDots = this.searchService.mData.search.aggs.map_dots;
							}

							if (this.searchService.mData.search.aggs.map_icons) {
								this.aMapIcons = this.searchService.mData.search.aggs.map_icons;
							}
						}
					}
				}
			}
		});
	}

	ngOnInit() {
		this.calculateContainerSizes()
	}


	onBoundsChanged(oNewBounds) {
		this.bounds = oNewBounds;
	}

	onMapIdle() {
		this.ref.detectChanges();
		// only trigger search if the resize didn't occur during window resize
		if (!this.bCurrentlyResizing) {
			this.doSearchFromBounds();
		}
	}

	onMarkerClick(sIgnore, iClickedIndex) {
		this.searchService.eThumbClick(iClickedIndex);
	}

	doSearchFromBounds() {
		this.searchService.addSetMapFilter(
			this.bounds.getSouthWest().lat(),
			this.bounds.getNorthEast().lat(),
			this.bounds.getSouthWest().lng(),
			this.bounds.getNorthEast().lng(),
			this.zoom
		);
		this.httpService.triggerSearch();
	}

	calculateContainerSizes() {
		const iScrollMargin = 24
		const iMapResultsGap = 8
		// get full width
		let iFullWidth = this.bothContainersWidth.nativeElement.offsetWidth

		// divide by two
		let iHalfWidth = iFullWidth / 2

		// how many map thumbs can fit in that half (half size - scroll / thumb + margin?
		const iThumbMargin = 8
		const iThumbWidth = 125
		const iTotalThumbWidthRequired = iThumbWidth + iThumbMargin

		let iPossibleColumns = iHalfWidth / iTotalThumbWidthRequired

		// round up or down, calculate required width for this many thumbs
		iPossibleColumns = Math.floor(iPossibleColumns)

		let iRequiredWidth = (iPossibleColumns * iTotalThumbWidthRequired) + iScrollMargin

		// set container sizes accordingly
		this.iResultsWidth = iRequiredWidth + 'px'
		this.iMapWidth = (iFullWidth - iRequiredWidth - iMapResultsGap) + 'px'
	}
}
