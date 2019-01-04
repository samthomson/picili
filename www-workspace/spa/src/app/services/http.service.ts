import { Injectable, EventEmitter } from '@angular/core';

import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';

import { GlobalVars } from './../../env';

import { SearchService } from './search.service';

import { Observable } from 'rxjs/Rx';
import 'rxjs/Rx';

@Injectable()
export class HttpService {

	authStatus: boolean = false;
	authStatusChanged = new EventEmitter<Boolean>();

	sToken: string;
	authTokenChanged = new EventEmitter<string>();

	jUser: any;
	userChanged = new EventEmitter<any>();


	mData: any;

	mDataChanged = new EventEmitter<any>();
	bSearchingChanged = new EventEmitter<any>();

	subCurrentSearchRequest: any;

	bMakingRequestToServer: boolean = false

	constructor(
		private http: HttpClient,
		private gbl: GlobalVars,
		private searchService: SearchService
	) {
		this.authStatus = !!localStorage.getItem(this.gbl.sAuthTokenName);
		this.sToken = localStorage.getItem(this.gbl.sAuthTokenName);
	}

	fetchPageState(sUsername): any {
		// this is the actual search method
		let authToken = localStorage.getItem(this.gbl.sAuthTokenName);
		let headers = new HttpHeaders()
			.append('Authorization', `Bearer ${authToken}`);


		let q = {};
		if (typeof this.searchService.mQuery.q !== 'undefined') {
			// send cursor to back end to get items afte
			q['q'] = this.searchService.mQuery.q;
		}

		if (typeof this.searchService.mQuery['filters'] !== 'undefined') {
			// send cursor to back end to get items afte
			q['filters'] = this.searchService.mQuery['filters'];
			// jParams.set('q', this.searchService.mQuery['filters']);
		}

		q['sort'] = this.searchService.sCurrentSort;

		let jParams = new HttpParams()
			.set('q', JSON.stringify(q))
			.set('page', this.searchService.iPage.toString());


		if (typeof this.searchService.sSearchMode !== 'undefined') {
			// send cursor to back end to get items afte
			jParams = jParams.set('searchmode', this.searchService.sSearchMode);
		}

		const options = {
			headers: headers,
			params: jParams,
			withCredentials: false
		}

		return this.http.get(`${this.gbl.sAPIBaseUrl}/app/pagestate/${sUsername}`, options)
		.map(
			(response: any) => {
				response = response;
				// this.mData = response;
				return response;
			}
		).catch((error: any) => {
			alert('error talking to server..')
			throw error;
		});
	}

	triggerSearch(bFirstSearch = true) {
		return new Promise((resolve, reject) => {
			if (this.subCurrentSearchRequest !== undefined) {
				// there is a pending search already, cancel it
				this.subCurrentSearchRequest.unsubscribe();
				this.searchService.bSearching = false;
			}

			if (!bFirstSearch) {
				this.searchService.bSearchingForMore = true;
			}
			this.searchService.bSearching = true;
			this.bSearchingChanged.emit(true);
			if (bFirstSearch) {
				this.searchService.bHasSearchResults = false;
				this.searchService.resetPage();
			}
			this.subCurrentSearchRequest = this.fetchPageState(this.gbl.sCurrentPageUsername).subscribe(
				(data) => {
					if (bFirstSearch) {
						// set
						this.searchService.mData = data;
					} else {
						// combine
						this.searchService.mData.search
						.data = data.search.data;

						let oldResults = this.searchService.mData.search
						.results;
						let newResults = data.search.results;

						oldResults = oldResults.concat(newResults);

						this.searchService.mData.search
						.results = oldResults;
					}
					this.searchService.bSearching = false;
					if (!bFirstSearch) {
						this.searchService.bSearchingForMore = false;
					}
					this.bSearchingChanged.emit(false);
					this.mDataChanged.emit(this.searchService.mData);

					if
					(
						typeof this.searchService.mData.search !== 'undefined' &&
						typeof this.searchService.mData.search.results !== 'undefined' && this.searchService.mData.search.results.length > 0
					) {
						this.searchService.bHasSearchResults = true;
					}
					resolve();
				},
				(err) => {
					this.searchService.bSearching = false;
					this.bSearchingChanged.emit(false);
					// todo - set a variable or something that will display state to ui
					resolve();
				}
			);
		});
	}

	getUser(): Observable<any> {
		const authToken = localStorage.getItem(this.gbl.sAuthTokenName);
		const headers = new HttpHeaders()
			.append('Authorization', `Bearer ${authToken}`);

		const options = {
			headers: headers,
			withCredentials: false
		}

		return this.http.get(`${this.gbl.sAPIBaseUrl}/app/me`, options)
		.map(
			(response: any) => {
				return response;
			}
		).catch((error: any) => {
			throw error;
			// return {'success': false, 'errors': error};
		});
	}

	getUserSettings() {
		const authToken = localStorage.getItem(this.gbl.sAuthTokenName);

		const headers = new HttpHeaders()
			.append('Authorization', `Bearer ${authToken}`);

			const options = {
			headers: headers,
			withCredentials: false
		}

		this.bMakingRequestToServer = true
		return this.http.get(`${this.gbl.sAPIBaseUrl}/app/settings`, options)
		.map(
			(response: any) => {
				this.bMakingRequestToServer = false
				return response;
			}
		).catch((error: any) => {
			this.bMakingRequestToServer = false
			throw error;
			// return {'success': false, 'errors': error};
		});
	}

	getHomeAggs() {
		const authToken = localStorage.getItem(this.gbl.sAuthTokenName);
		const headers = new HttpHeaders()
			.append('Authorization', `Bearer ${authToken}`);

		const options = {
			headers: headers,
			withCredentials: false
		}

		this.bMakingRequestToServer = true

		return this.http.get(`${this.gbl.sAPIBaseUrl}/app/homeaggs`, options)
			.map(
				(response: any) => {
					this.bMakingRequestToServer = false
					return response.home_aggs;
				}
			).catch((err: any) => {
				console.log('error getting home aggs');
				throw err
				// return {'success': false, 'errors': err}
			})
	}

	updateDropboxFolder(sFolder) {
		const authToken = localStorage.getItem(this.gbl.sAuthTokenName);

		const jParams = new HttpParams()
			.set('folder', sFolder);

		const headers = new HttpHeaders()
			.append('Content-Type', 'application/x-www-form-urlencoded')
			.append('Authorization', `Bearer ${authToken}`);

		const options = { headers: headers, withCredentials: false }

		return this.http.put(
			this.gbl.sAPIBaseUrl + '/app/settings/dropboxfolder',
			jParams,
			options
		)
			.map(
				(response: any) => {

					let data = response;

					let bSuccess = data.success;

					if (bSuccess) {
						return {'success': true};
					} else {
						// return false to indicate failed login
						return {'success': false, 'errors': data.errors};
					}
			});
	}

	updatePrivacy(bPublic): Observable<any> {
		const authToken = localStorage.getItem(this.gbl.sAuthTokenName);

		const jParams = new HttpParams()
			.set('public', this.phpBool(bPublic));

		const headers = new HttpHeaders()
			.append('Content-Type', 'application/x-www-form-urlencoded')
			.append('Authorization', `Bearer ${authToken}`);

		const options = { headers: headers, withCredentials: false }

		return this.http.put(
			this.gbl.sAPIBaseUrl + '/app/settings/privacy',
			jParams.toString(),
			options
		)
			.map(
				(response: any) => {

					let data = response;

					let bSuccess = data.success;

					if (bSuccess) {
						return {'success': true};
					} else {
						// return false to indicate failed login
						return {'success': false, 'errors': data.errors};
					}
			});
	}

	dropboxOAuth() {
		return this.gbl.sOAUTHAPIBaseUrl + '/oauth/dropbox' + '?token=' + this.sToken;
	}

	disconnectDropbox() {
		const authToken = localStorage.getItem(this.gbl.sAuthTokenName);
		const headers = new HttpHeaders()
			.append('Authorization', `Bearer ${authToken}`);

		const options = {
			headers: headers,
			withCredentials: false
		}

		return this.http.delete(`${this.gbl.sAPIBaseUrl}/app/settings/dropbox`, options)
		.map(
			(response: any) => {
				return response;
			}
		).catch((error: any) => {
			throw error;
			// return {'success': false, 'errors': error};
		});
	}

	phpBool(bBool) {
		return bBool ? '1' : '0';
	}

	getFileInfo(sFileId) {
		const authToken = localStorage.getItem(this.gbl.sAuthTokenName);

		const headers = new HttpHeaders()
			.append('Authorization', `Bearer ${authToken}`);

		const jParams = new HttpParams()
			.set('file', sFileId);

		const options = {
			headers: headers,
			params: jParams,
			withCredentials: false
		}

		this.bMakingRequestToServer = true

		return this.http.get(`${this.gbl.sAPIBaseUrl}/app/fileinfo`, options)
		.map(
			(response: any) => {
				this.bMakingRequestToServer = false
				return response;
			}
		).catch((error: any) => {
			this.bMakingRequestToServer = false
			throw error;
			// return {'success': false, 'errors': error};
		});
	}

	attemptPreload(iFileIndex) {
		// todo
		const sSize = 'xl';
		let imgPreload = new Image();
		imgPreload.src = 'https://s3-eu-west-1.amazonaws.com/picili-bucket/t/' + this.gbl.sCurrentPageUsername + '/' + sSize + this.searchService.mData.search.results[iFileIndex].id + '.jpg'
	}

	preloadActiveDelta(iDelta) {
		// find file in deltas position and load it
		let iTemp = this.searchService.iActiveThumb + iDelta

		if (iTemp >= this.searchService.mData.search.results.length) {
			iTemp = 0
		}
		if (iTemp < 0) {
			iTemp = this.searchService.mData.search.results.length - 1
		}

		this.attemptPreload(iTemp)
	}

	preloadNeighboursToLightIndex() {
		this.preloadActiveDelta(1)
		this.preloadActiveDelta(2)
		this.preloadActiveDelta(-1)
		this.preloadActiveDelta(-2)
	}
}
