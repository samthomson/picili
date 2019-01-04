import { Component, OnInit } from '@angular/core';
import { SearchService, HttpService } from './../../../services';

@Component({
    selector: 'app-sort-select',
    templateUrl: './sort-select.component.html'
})
export class SortSelectComponent implements OnInit {

  constructor(
      private searchService: SearchService,
      private httpService: HttpService
  ) { }

    ngOnInit() {
    }
    setSort(sNewSortMode) {
      this.searchService.sCurrentSort = sNewSortMode;
      this.searchService.bSortChanged = true;
      this.httpService.triggerSearch();
      this.searchService.updateURLToVars();
    }

}
