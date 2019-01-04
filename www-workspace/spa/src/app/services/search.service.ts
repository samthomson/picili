import { Injectable, EventEmitter } from '@angular/core';
import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';


import { GlobalVars } from './../../env';

import * as moment from 'moment';

import 'rxjs/Rx';

@Injectable()
export class SearchService {

	mQuery: any = {'filters': [], 'q': '', 'sort': ''};
	iPage: number = 1;
	sSearchMode: string;
	mData: any;
	queryChanged = new EventEmitter<any>();

	sCurrentSort = 'date_desc';

	bSearching: boolean = false;
	bSearchingForMore: boolean = false;
	bHasSearchResults: boolean = false;
	bSortChanged: boolean = false;
	aResults;

	eeThumbClick = new EventEmitter<any>();
	eeLightboxClose = new EventEmitter<any>();
	iActiveThumb: number = -1;

	sCalendarSearchMode: string = 'month';
	sDate: string = '01/02/2017';
	sCurrentDateDisplay: string = 'giraffe';

	mdDate: any;
	eeDateFromUrl = new EventEmitter<any>();
	eeDatechange = new EventEmitter<any>();

	sPeopleSearchGender: string = 'both';
	sPeopleSearchState: string = 'all';
	sPeopleSearchGrouping: string = 'any';

	constructor(
		private route: ActivatedRoute,
		private gbl: GlobalVars,
		private location: Location/*,
        private httpService: HttpService*/
	) {
		this.mQuery = {'filters': [], 'q': '', 'sort': ''};
		this.mdDate = moment();
	}

	eThumbClick(iIndice) {
		this.iActiveThumb = iIndice
		this.eeThumbClick.emit(this.iActiveThumb)
	}

	getQVars(): Object {
		let rParams = {};
		if (typeof this.mQuery['q'] !== 'undefined') {
			if (this.mQuery['q'] !== '') {
				rParams['q'] = this.mQuery['q'];
			}
		}

		if (this.mQuery['filters'] !== 'undefined') {
			if (this.mQuery['filters'].length > 0) {
				rParams['filters'] = JSON.stringify(this.mQuery['filters']);
			}
		}

		// sort?
		if (this.bSortChanged) {
			rParams['sort'] = this.sCurrentSort;
		}

		return rParams;
	}
	updateURLToVars() {
		let sArgs = this.getQueryString();
		this.location.go(sArgs);
	}

	getQueryString(): string {
		// who calls this? - used when we 'change state' and want the url to match
		let sReturn = this.gbl.sCurrentPageUsername;

		if (this.sSearchMode !== 'default') {
			sReturn += '/' + this.sSearchMode;
		}

		if (!this.bEmptyQuery() || this.mQuery['filters'].length > 0) {
			if (!this.bEmptyQuery()) {
				sReturn += '?q=' + this.mQuery['q'];
			}

			if (this.mQuery['filters'].length > 0) {
				if (this.bEmptyQuery()) {
					sReturn += '?';
				} else {
					sReturn += '&';
				}

				sReturn += 'filters=' + JSON.stringify(this.mQuery['filters']);
			}
		}

		if (this.bSortChanged) {
			sReturn += '&sort=' + this.sCurrentSort;
		}

		return sReturn;
	}

	setQueryText(setQueryString) {
		this.mQuery.q = setQueryString;
	}

	setDate(mDate) {
		this.mdDate = mDate;
		// parse the moment date into a literal date which we'll use in search value of cal search
		this.sDate = this.mdDate.format('DD/MM/YYYY');
	}

	bEmptyQuery() {
		let value = this.mQuery.q;
		return typeof value == 'string' && !value.trim() || typeof value == 'undefined' || value === null;
	}

	addFilter(sType, sDisplay, sValue) {
		if (typeof this.mQuery['filters'] === 'undefined') {
			this.mQuery['filters'] = [];
		}
		let aF = {'type': sType, 'display': sDisplay, 'value': sValue};
		this.mQuery['filters'].push(aF);
		this.updateURLToVars();
	}

	removeFilterByType(sType) {
		this.mQuery = this.removeFilterByTypeOnQueryObject(this.mQuery, sType)
	}

	removeFilterByTypeOnQueryObject(oQueryObject, sType) {
		let iPositionAt = -1;
		for (let i = 0; i < oQueryObject['filters'].length; i++) {
			if (oQueryObject['filters'][i].type === sType) {
				iPositionAt = i;
			}
		}
		if (iPositionAt !== -1) {
			oQueryObject['filters'].splice(iPositionAt, 1);
		}
		return oQueryObject
	}

	addSetMapFilter(iLatMin, iLatMax, iLonMin, iLonMax, iZoom) {
		// there can only be one map query, so we set it or update it
		let sValue = iLatMin + ',' + iLatMax + ',' + iLonMin + ',' + iLonMax + ',' + iZoom;

		this.removeFilterByType('map');

		this.addFilter('map', 'map', sValue);
	}
	addSetCalendarFilter(sMode, sDisplay, sValue) {
		this.removeFilterByType('calendar');

		this.addFilter('calendar', sDisplay, sMode + ':' + sValue);
	}
	removeFilter(iIndex) {
		this.mQuery['filters'].splice(iIndex, 1);
		this.updateURLToVars();
	}
	clearFilters() {
		this.mQuery['filters'] = [];
		this.updateURLToVars();
	}

	determineLocalVarsByParsingUrlVars() {
		let bFoundInUrl = false;
		let bCalVars = false;
		// if there's a calendar query parse out mode and date, or set defaults
		for (let i = 0; i < this.mQuery['filters'].length; i++) {
			if (this.mQuery['filters'][i].type === 'calendar') {
				bFoundInUrl = true;
				// get value as mode:date
				let saModeValue = this.mQuery['filters'][i].value.split(':');

				let sMode = saModeValue[0];
				let sDate = saModeValue[1];

				this.sCalendarSearchMode = sMode;
				this.sDate = sDate;
				this.mdDate = moment(sDate, 'DD/MM/YYYY');
				this.eeDateFromUrl.emit();
				// emit datechange so that the display date is updated on calendar
				this.eeDatechange.emit();

				bCalVars = true;
			}
		}
		// did we find cal vars in url
		return (bFoundInUrl && bCalVars)
	}

	resetPage() {
		this.iPage = 1
	}
}
