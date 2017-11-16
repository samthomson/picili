import { Injectable, EventEmitter } from '@angular/core';
import { Location } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { Http, Headers, Response, URLSearchParams, RequestOptions } from '@angular/http';

import { HttpService } from './http.service';

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
        // this.mQuery.q = '';
        // this.mQuery['q'] = '';
        // console.log("mQuery:");
        // console.log(this.mQuery);

        this.mdDate = moment();
    }

    eThumbClick(iIndice)
    {
        // console.log("eThumbClick(iIndice)")
        this.iActiveThumb = iIndice
        this.eeThumbClick.emit(this.iActiveThumb)
    }

    getQVars() : Object{
        let rParams = {};
        if (typeof this.mQuery['q'] !== "undefined")
        {
            if (this.mQuery['q'] !== '')
            {
                rParams['q'] = this.mQuery['q'];
            }
        }

        if (this.mQuery['filters'] !== "undefined")
        {
            if (this.mQuery['filters'].length > 0)
            {
                rParams['filters'] = JSON.stringify(this.mQuery['filters']);
            }
        }

        // sort?
        if (this.bSortChanged)
        {
            rParams['sort'] = this.sCurrentSort;
        }

        return rParams;
    }
    updateURLToVars()
    {
        // console.log("qa vars: ");
        let sArgs = this.getQueryString();
        // console.log(sArgs);
        this.location.go(sArgs);
    }

    getQueryString() : string
    {
        // who calls this? - used when we 'change state' and want the url to match
        let sReturn = this.gbl.sCurrentPageUsername;

        if(this.sSearchMode !== 'default')
        {
            sReturn += '/' + this.sSearchMode;
        }

        if(!this.bEmptyQuery() || this.mQuery['filters'].length > 0)
        {
            if(!this.bEmptyQuery())
            {
                sReturn += '?q=' + this.mQuery['q'];
            }

            if(this.mQuery['filters'].length > 0)
            {
                if(this.bEmptyQuery())
                {
                    sReturn += '?';
                }else{
                    sReturn += '&';
                }

                sReturn += 'filters=' + JSON.stringify(this.mQuery['filters']);
            }
        }

        if(this.bSortChanged)
        {
            sReturn += '&sort=' + this.sCurrentSort;
        }

        return sReturn;
    }

    setQueryText(setQueryString)
    {
        this.mQuery.q = setQueryString;
    }

    setDate(mDate)
    {        
        this.mdDate = mDate;
        // parse the moment date into a literal date which we'll use in search value of cal search
        this.sDate = this.mdDate.format('DD/MM/YYYY'); 
    }

    bEmptyQuery() {
        let value = this.mQuery.q;
        return typeof value == 'string' && !value.trim() || typeof value == 'undefined' || value === null;
    }

    addFilter(sType, sDisplay, sValue)
    {
        // console.log("in 'addFilter'");
        if(typeof this.mQuery['filters'] === 'undefined')
        {
            this.mQuery['filters'] = [];
        }
        var aF = {'type': sType, 'display': sDisplay, 'value': sValue};
        this.mQuery['filters'].push(aF);
        this.updateURLToVars();
        // console.log("SearchService.mQuery['filters']");
        // console.log(this.mQuery['filters']);
    }

    removeFilterByType(sType)
    {
        let iPositionAt = -1;
        for(let i = 0; i < this.mQuery['filters'].length; i++)
        {
            if(this.mQuery['filters'][i].type === sType)
            {
                iPositionAt = i;
            }
        }
        if(iPositionAt !== -1)
        {
            this.mQuery['filters'].splice(iPositionAt, 1);
        }
    }

    addSetMapFilter(iLatMin, iLatMax, iLonMin, iLonMax, iZoom)
    {
        // console.log("addsetmapfilter");
        // there can only be one map query, so we set it or update it
        var sValue = iLatMin + ',' + iLatMax + ',' + iLonMin + ',' + iLonMax + ',' + iZoom;

        this.removeFilterByType('map');

        this.addFilter('map', 'map', sValue);
        // console.log(this.mQuery['filters']);
    }
    addSetCalendarFilter(sMode, sDisplay, sValue)
    {
        this.removeFilterByType('calendar');

        this.addFilter('calendar', sDisplay, sMode + ':' + sValue);
    }
    removeFilter(iIndex)
    {
        this.mQuery['filters'].splice(iIndex, 1);
        this.updateURLToVars();
    }
    clearFilters()
    {
        this.mQuery['filters'] = [];
        this.updateURLToVars();
    }

    determineLocalVarsByParsingUrlVars()
    {
        console.log('\n\nsearchservice: determineLocalVarsByParsingUrlVars')
        let bFoundInUrl = false;
        let bCalVars = false;
        // if there's a calendar query parse out mode and date, or set defaults
        for(let i = 0; i < this.mQuery['filters'].length; i++)
        {
            if(this.mQuery['filters'][i].type === 'calendar')
            {
                bFoundInUrl = true;
                // get value as mode:date
                var saModeValue = this.mQuery['filters'][i].value.split(':');

                var sMode = saModeValue[0];
                var sDate = saModeValue[1];

                this.sCalendarSearchMode = sMode;
                this.sDate = sDate;
                this.mdDate = moment(sDate, 'DD/MM/YYYY');
                this.eeDateFromUrl.emit();
                // emit datechange so that the display date is updated on calendar
                this.eeDatechange.emit();

                bCalVars = true;
                console.log('\n\n-- found in url')
            }
        }
        // console.log(`\n\n\determineLocalVarsByParsingUrlVars: cal component:\nbFoundInUrl: ${bFoundInUrl}\nbCalVars: ${bCalVars}\n`)
        // did we find cal vars in url
        return (bFoundInUrl && bCalVars)
        /*

        if(!bFoundInUrl)
        {
            // set defaults
            if(!bCalVars)
            {
                // calender search vars
                this.sCalendarSearchMode = 'month';
                this.mdDate = moment().startOf('month');
                this.sDate = this.mdDate.format('DD/MM/YYYY');
                // console.log('\n\n\neeDatechange\n\n\n')
                this.addSetCalendarFilter(this.sCalendarSearchMode, this.sCurrentDateDisplay, this.sDate)

                this.eeDatechange.emit();
                console.log('\n\n-- not found in url, use defaults')
            }
        }
        */
    }

    resetPage()
    {
        this.iPage = 1
    }
}
