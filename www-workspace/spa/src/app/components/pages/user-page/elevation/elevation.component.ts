import { Component, OnInit } from '@angular/core';
import { Options } from 'ng5-slider';

import { HttpService, SearchService } from './../../../../services';

@Component({
  selector: 'app-elevation',
  templateUrl: './elevation.component.html'
})
export class ElevationComponent implements OnInit {

	minValue: number = 20;
	maxValue: number = 80;
	options: Options = {
		floor: 0,
		ceil: 7000,
		step: 1
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
		console.log('\n\nSEARCH NOW\n\n')

		// build query
		this.searchService.addElevationFilter(this.minValue, this.maxValue)
		this.httpService.triggerSearch()

	}, iInterval);
  }
}
