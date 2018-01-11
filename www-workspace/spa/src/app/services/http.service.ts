import { Injectable, EventEmitter } from '@angular/core';
import { Http, Headers, Response, URLSearchParams, RequestOptions } from '@angular/http';


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

    constructor(
        private http: Http,
        private gbl: GlobalVars,
        private searchService: SearchService
    ) {
        this.authStatus = !!localStorage.getItem(this.gbl.sAuthTokenName);
        this.sToken = localStorage.getItem(this.gbl.sAuthTokenName);
    }

    fetchPageState(sUsername) : any {

        let authToken = localStorage.getItem(this.gbl.sAuthTokenName);
        let headers = new Headers();
        let jParams = new URLSearchParams();

        if (typeof this.searchService.sSearchMode !== 'undefined')
        {
            // send cursor to back end to get items afte
            jParams.set('searchmode', this.searchService.sSearchMode);
        }

        let q = {};
        if (typeof this.searchService.mQuery.q !== 'undefined')
        {
            // send cursor to back end to get items afte
            q['q'] = this.searchService.mQuery.q;
        }

        if (typeof this.searchService.mQuery['filters'] !== 'undefined')
        {
            // send cursor to back end to get items afte
            q['filters'] = this.searchService.mQuery['filters'];
            // jParams.set('q', this.searchService.mQuery['filters']);
        }

        q['sort'] = this.searchService.sCurrentSort;

        jParams.set('q', JSON.stringify(q));
        jParams.set('page', this.searchService.iPage.toString());

        headers.append('Authorization', `Bearer ${authToken}`);

        let options = new RequestOptions(
            {
                headers: headers,
                search: jParams.toString(),
                withCredentials: false
            }
        );

        return this.http.get(`${this.gbl.sAPIBaseUrl}/app/pagestate/${sUsername}`, options)
        .map(
            (response: Response) => {
                response = response.json();
                // this.mData = response;
                return response;
            }
        ).catch((error: any) => {
            alert('error talking to server..')
            throw error;
        });
    }

    triggerSearch(bFirstSearch = true)
    {        
        return new Promise((resolve, reject) => {
            if(this.subCurrentSearchRequest !== undefined)
            {
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
                    if(bFirstSearch)
                    {
                        // set
                        this.searchService.mData = data;
                    }else{
                        // combine
                        this.searchService.mData.search
                        .data = data.search.data;

                        var oldResults = this.searchService.mData.search
                        .results;
                        var newResults = data.search.results;

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
                        typeof this.searchService.mData.search.results !== 'undefined' &&this.searchService.mData.search.results.length > 0
                    ){
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

    getUser() : Observable<any>
    {
        let authToken = localStorage.getItem(this.gbl.sAuthTokenName);
        let headers = new Headers();
        let jParams = new URLSearchParams();


        headers.append('Authorization', `Bearer ${authToken}`);

        let options = new RequestOptions(
            {
                headers: headers,
                withCredentials: false
            }
        );

        return this.http.get(`${this.gbl.sAPIBaseUrl}/app/me`, options)
        .map(
            (response: Response) => {
                return response.json();
            }
        ).catch((error: any) => {
            throw error;
            // return {'success': false, 'errors': error};
        });
    }

    getUserSettings()
    {
        let authToken = localStorage.getItem(this.gbl.sAuthTokenName);
        let headers = new Headers();
        let jParams = new URLSearchParams();


        headers.append('Authorization', `Bearer ${authToken}`);

        let options = new RequestOptions(
            {
                headers: headers,
                withCredentials: false
            }
        );

        return this.http.get(`${this.gbl.sAPIBaseUrl}/app/settings`, options)
        .map(
            (response: Response) => {
                return response.json();
            }
        ).catch((error: any) => {
            throw error;
            //return {'success': false, 'errors': error};
        });
    }

    updateDropboxFolder(sFolder)
    {
        let authToken = localStorage.getItem(this.gbl.sAuthTokenName);

        let jParams = new URLSearchParams();
        jParams.set('folder', sFolder);

        let headers = new Headers();
		headers.append('Content-Type', 'application/x-www-form-urlencoded');

        headers.append('Authorization', `Bearer ${authToken}`);


        let options = new RequestOptions({ headers: headers, withCredentials: false });


        return this.http.put(
            this.gbl.sAPIBaseUrl + '/app/settings/dropboxfolder',
            jParams.toString(),
            options
        )
            .map(
                (response: Response) => {

                    let data = response.json();

                    let bSuccess = data.success;

                    if (bSuccess) {
                        return {'success': true};
                    }else{
                        // return false to indicate failed login
                        return {'success': false, 'errors': data.errors};
                    }
            });
    }

    updatePrivacy(bPublic) : Observable<any>{
        let authToken = localStorage.getItem(this.gbl.sAuthTokenName);

        let jParams = new URLSearchParams();
        jParams.set('public', this.phpBool(bPublic));

        let headers = new Headers();
		headers.append('Content-Type', 'application/x-www-form-urlencoded');

        headers.append('Authorization', `Bearer ${authToken}`);


        let options = new RequestOptions({ headers: headers, withCredentials: false });


        return this.http.put(
            this.gbl.sAPIBaseUrl + '/app/settings/privacy',
            jParams.toString(),
            options
        )
            .map(
                (response: Response) => {

                    let data = response.json();

                    let bSuccess = data.success;

                    if (bSuccess) {
                        return {'success': true};
                    }else{
                        // return false to indicate failed login
                        return {'success': false, 'errors': data.errors};
                    }
            });
    }

    dropboxOAuth()
    {
        return this.gbl.sOAUTHAPIBaseUrl + '/oauth/dropbox' + '?token=' + this.sToken;
    }

    disconnectDropbox()
    {
        let authToken = localStorage.getItem(this.gbl.sAuthTokenName);
        let headers = new Headers();
        let jParams = new URLSearchParams();


        headers.append('Authorization', `Bearer ${authToken}`);

        let options = new RequestOptions(
            {
                headers: headers,
                withCredentials: false
            }
        );

        return this.http.delete(`${this.gbl.sAPIBaseUrl}/app/settings/dropbox`, options)
        .map(
            (response: Response) => {
                return response.json();
            }
        ).catch((error: any) => {
            throw error;
            //return {'success': false, 'errors': error};
        });
    }

    phpBool(bBool)
    {
        return bBool ? '1' : '0';
    }

    getFileInfo(sFileId) {
        let authToken = localStorage.getItem(this.gbl.sAuthTokenName);

        let headers = new Headers();
        headers.append('Authorization', `Bearer ${authToken}`);

        let jParams = new URLSearchParams();
        jParams.set('file', sFileId);

        let options = new RequestOptions(
            {
                headers: headers,
                search: jParams.toString(),
                withCredentials: false
            }
        );

        return this.http.get(`${this.gbl.sAPIBaseUrl}/app/fileinfo`, options)
        .map(
            (response: Response) => {
                response = response.json();
                // this.mData = response;
                return response;
            }
        ).catch((error: any) => {
            throw error;
            //return {'success': false, 'errors': error};
        });
    }

    attemptPreload(iFileIndex) {
        // todo

        let sSize = 'xl';
        var imgPreload = new Image();
    	imgPreload.src = 'https://s3-eu-west-1.amazonaws.com/picili-bucket/t/'+ this.gbl.sCurrentPageUsername +'/' + sSize + this.searchService.mData.search.results[iFileIndex].id+'.jpg'
    }

    preloadActiveDelta(iDelta) {
        // find file in deltas position and load it
        let iTemp = this.searchService.iActiveThumb + iDelta

        if(iTemp >= this.searchService.mData.search.results.length)
        {
            iTemp = 0
        }
        if(iTemp < 0)
        {
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
