import { Component, OnInit, ElementRef, ViewChild } from '@angular/core';
import { SearchService, HttpService } from './../../../services';

declare var $: any;

@Component({
	selector: 'app-sort-select',
	templateUrl: './sort-select.component.html'
})
export class SortSelectComponent implements OnInit {

    @ViewChild('dropdown', { read: ElementRef }) dropdown: ElementRef<any>;

	constructor(
		private searchService: SearchService,
		private httpService: HttpService
	) { }

	ngOnInit() {
        $(this.dropdown.nativeElement).dropdown()
	}
	setSort(sNewSortMode) {
		this.searchService.sCurrentSort = sNewSortMode;
		this.searchService.bSortChanged = true;
		this.httpService.triggerSearch();
		this.searchService.updateURLToVars();
	}

}
