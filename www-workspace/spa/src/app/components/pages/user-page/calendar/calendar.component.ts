import { Component, OnInit  } from '@angular/core';

import { HttpService, SearchService, HelperService } from './../../../../services';
import { GlobalVars } from './../../../../../env';

import { UserPageComponent } from '../user-page.component'

import * as moment from 'moment';

@Component({
  selector: 'app-calendar',
  templateUrl: './calendar.component.html'
})
export class CalendarComponent implements OnInit {

	private aAvailableDates: any = []
	private oDateResults: any = null
	private varrr: any = '';
	parentComponent: UserPageComponent;
	private oCurrentDate = moment().format('YYYY-MM-DD')
	private aLiteralDays: string[] = []

	private aAvailableMonths: any = {}
	private aAvailableMonthKeys: string[] = []

	constructor(
		private searchService: SearchService,
		private httpService: HttpService,
		private helperService: HelperService,
		private gbl: GlobalVars
	) {
	}

	setResults(oResults) {
		this.oDateResults = oResults
	}

	goToDate(sDate) {
		// change to day mode
		this.searchService.sCalendarSearchMode = 'day'

		// set date
		this.searchService.setDate(moment(sDate));

		this.searchService.addSetCalendarFilter(this.searchService.sCalendarSearchMode, this.searchService.mdDate.format('ddd Do'), this.searchService.sDate);


		// trigger search
		this.httpService.triggerSearch();
		this.searchService.eeDatechange.emit();
	}

	goToMonthFromYearView(event) {
		this.searchService.sCalendarSearchMode = 'month'

		// set date
		this.searchService.setDate(event.oDate);

		this.searchService.addSetCalendarFilter(this.searchService.sCalendarSearchMode, this.searchService.mdDate.format('ddd Do'), this.searchService.sDate);


		// trigger search
		this.httpService.triggerSearch();
		this.searchService.eeDatechange.emit();
	}

	goToDateFromYearView(event) {
		this.searchService.sCalendarSearchMode = 'day'

		// set date
		this.searchService.setDate(event.oDate);

		this.searchService.addSetCalendarFilter(this.searchService.sCalendarSearchMode, this.searchService.mdDate.format('ddd Do'), this.searchService.sDate);


		// trigger search
		this.httpService.triggerSearch();
		this.searchService.eeDatechange.emit();
	}

	formatWeekDateHeader(sDate) {
		// turn '2016-06-21' into 21st June 2016
		return moment(sDate).format('dddd Do');
	}


	ngOnInit() {
		let bCalVarsInUrl = this.searchService.determineLocalVarsByParsingUrlVars()

		if (!bCalVarsInUrl) {
			// no cal vars in url, lets set defaults and update ui before searching
			// calender search vars
				this.searchService.sCalendarSearchMode = 'month';
				this.searchService.mdDate = moment().startOf('month');
				this.searchService.sDate = this.searchService.mdDate.format('DD/MM/YYYY');
				// make sure date literal is parsed
				this.searchService.eeDatechange.emit();
				this.searchService.sCurrentDateDisplay = this.helperService.parseDisplayDates(
					this.searchService.sCalendarSearchMode,
					this.searchService.mdDate
				).sCurrentDateDisplay

				// add calendar filter to ui
				setTimeout(() => {
					this.searchService.addSetCalendarFilter(
						this.searchService.sCalendarSearchMode,
						this.searchService.sCurrentDateDisplay,
						this.searchService.sDate
					)
					this.httpService.triggerSearch();
				}, 1)

				// this.eeDatechange.emit();
		} else {
			this.httpService.triggerSearch();
		}
		// this.httpService.triggerSearch();

		// don't do this, it will overload url cal search  this.goToDate(moment())
		// this.searchService.eeDatechange.emit();
		// this.searchService.eeDatechange.emit();
		// EMIT TO PARENT HERE

		this.httpService.bSearchingChanged.subscribe((bSearching) => {
			// search started or ended, if started, clear calendar
			if (bSearching) {
				this.aAvailableDates = []
				this.aAvailableMonths = {}
				this.aAvailableMonthKeys = []
				this.oDateResults = null
			}
		})

		this.httpService.mDataChanged.subscribe((data) => {
			// calculate available days, to be used in the UI

			// populate an array of dates, for the current period. Where the period is either a week or a month. and use actual dates starting with the set date
			// searchService.sCalendarSearchMode
			// this.searchService.mdDate
			let mdDateCopy = this.searchService.mdDate.clone()
			this.aAvailableDates = []
			this.aAvailableMonths = {}
			this.aAvailableMonthKeys = []
			let oLocalDateResults: any = []


			if
			(
				typeof this.searchService.mData.search !== 'undefined' &&
				typeof this.searchService.mData.search.aggs !== 'undefined'
			) {
				this.aLiteralDays = [];
				switch (this.searchService.sCalendarSearchMode) {
					case 'week':
						for (let iDayOfWeekCount = 0; iDayOfWeekCount < 7; iDayOfWeekCount++) {
							this.aAvailableDates.push(this.searchService.mdDate.clone().add(iDayOfWeekCount, 'days').format('YYYY-MM-DD'))
						}
						break;
					case 'month':
						for (let iDayOfMonthCount = 0; iDayOfMonthCount < this.searchService.mdDate.daysInMonth(); iDayOfMonthCount++) {
							this.aAvailableDates.push(this.searchService.mdDate.clone().add(iDayOfMonthCount, 'days').format('YYYY-MM-DD'))

							// make headers
							if (iDayOfMonthCount < 7) {
								this.aLiteralDays.push(this.searchService.mdDate.clone().add(iDayOfMonthCount, 'days').format('dddd').substr(0, 1))
							}
						}
						break;
					case 'year':
						const sYearSearched: string = this.searchService.mdDate.clone().format('YYYY')

						// create a collection to represent months which results can be split across
						for (let cMonthOfYear = 1; cMonthOfYear < 13; cMonthOfYear++) {
							let sMonth: string = String(cMonthOfYear)
							if (cMonthOfYear < 10) {
								sMonth = '0' + sMonth
							}
							const sKey = `${sYearSearched}-${sMonth}`
							this.aAvailableMonths[sKey] = []
							this.aAvailableMonthKeys.push(sKey)
						}

						// now go through all results and place them in the correct month
						if (this.searchService.mData.search.aggs.length > 0) {
							this.searchService.mData.search.aggs.forEach(oDate => {
								const sMonthKey: string = oDate.name.substr(0, 7)

								if (this.aAvailableMonths[sMonthKey]) {
									if (oDate.count > 0) {
										this.aAvailableMonths[sMonthKey].push({
											dateKey: oDate.name.substr(8, 2),
											count: oDate.count,
											sImageID: oDate.files[0] || null
										})
									}
								}
							})
						}
						// now we have all the days in the year sorted into months
						break
				}

				// then parse in actual day results (day/week/month mode)
				for (let iDays = 0; iDays < this.aAvailableDates.length; iDays++) {
					// look for day-result, add it, or add empty
					let bFoundSomething = false;

					// search for an aggregation of files for this date
					let sKey = '"' + this.aAvailableDates[iDays] + '"'
					for (let iDayResults = 0; iDayResults < this.searchService.mData.search.aggs.length && !bFoundSomething; iDayResults++) {

						if (this.searchService.mData.search.aggs[iDayResults].name.startsWith(this.aAvailableDates[iDays])) {
							bFoundSomething = true

							oLocalDateResults.push( this.searchService.mData.search.aggs[iDayResults]
							)
						}
					}

					if (!bFoundSomething) {
						oLocalDateResults.push({})
					}
				}
				// we have built an object of dates, some containing a result agg object

				this.oDateResults = oLocalDateResults
			}
		});
	}
}
