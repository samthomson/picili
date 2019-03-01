import { Component, OnInit } from '@angular/core';
import { Options } from 'ng5-slider';

import { HttpService, SearchService } from './../../../../services';

@Component({
  selector: 'app-elevation',
  templateUrl: './elevation.component.html'
})
export class ElevationComponent implements OnInit {

	minValue: number = -500;
	maxValue: number = 7000;
	options: Options = {
		floor: 0,
		ceil: 7000,
		step: 1,
		getPointerColor: () => { return '#d32f2f' },
		selectionBarGradient: {
			from: '#d32f2f',
			to: '#d32f2f'
		  }
	};

	searchTimeout;


	constructor(
		private httpService: HttpService,
		private searchService: SearchService
	) { }

  ngOnInit() {
  }

  onValueChange(bInstant: boolean = false) {
	// trigger a search
	// add a delay if from the slider
	// console.log('\nmin: ', this.minValue)
	// console.log('max: ', this.maxValue)

	clearTimeout(this.searchTimeout)

	// let iInterval = bInstant ? 0 : 2000
	let iInterval = 500

	this.searchTimeout = setTimeout(() => {
		// build query
		this.searchService.addElevationFilter(this.minValue, this.maxValue)
		this.httpService.triggerSearch()

	}, iInterval);
  }
}
