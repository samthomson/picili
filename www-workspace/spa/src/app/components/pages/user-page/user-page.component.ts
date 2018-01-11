import { Component, OnInit, OnDestroy, Injectable } from '@angular/core';
import { ActivatedRoute, Params } from '@angular/router';
import { HttpService, SearchService, HelperService } from './../../../services';

import { PlatformLocation } from '@angular/common';

import { GlobalVars } from './../../../../env';

import * as moment from 'moment';

@Component({
  selector: 'app-user-page',
  templateUrl: './user-page.component.html'
})

@Injectable()
export class UserPageComponent implements OnInit, OnDestroy {

    private sSearchMode: string;
    // private sCurrentPageUsername: string;
    private mData: any;
    private mQuery: any;
    private tSearchEventTimeout: any;

    private mLocalData: any;
    private bLightboxOpen:boolean = false;

    // subscriptions
    private subQueryChanged;
    private subLightboxOpen;
    private subLightboxClose;
    private subDateFromUrl;
    private subDateFromSearch;


    private sCurrentDateDisplay: string;
    private sCurrentHeaderDisplay: string;
    
    private bSearchServiceSearching: boolean = false;


    constructor(
        private route: ActivatedRoute,
        private httpService: HttpService,
        private searchService: SearchService,
        private helperService: HelperService,        
        private gbl: GlobalVars,
        private location: PlatformLocation
    ) {
        this.gbl.sCurrentPageUsername = route.snapshot.params['username'];

        this.searchService.sSearchMode = (typeof route.snapshot.params['searchmode'] === "undefined") ? 'default' : route.snapshot.params['searchmode'];
        this.searchService.iPage = 1;

        //
        // get query vars
        //
        this.route.queryParams.subscribe(params => {
            let q = params['q'];
            this.searchService.setQueryText(q);


            if(typeof params['sort'] !== "undefined")
            {
                this.searchService.sCurrentSort = params['sort'];
                this.searchService.bSortChanged = true;
            }else{
                this.searchService.sCurrentSort = 'date_desc';
            }

            this.searchService.mQuery['filters'] = (typeof params['filters'] === "undefined") ? [] : JSON.parse(params['filters']);

            this.subDateFromUrl = this.searchService.eeDateFromUrl.subscribe(() => {
                this.setLocalDisplayDate();
            });

            //// this.searchService.determineLocalVarsByParsingUrlVars();

            // adding this here so that when user presses back and the url changes we trigger this
            this.changedSearchPage(this.searchService.sSearchMode);

            // this.searchService.mQuery['filters'] = JSON.parse(params['filters']);
        });

        // this.httpService.triggerSearch();

        location.onPopState(() => {
            // user pressed back, but not just while on this page, possibly also TO this page
            // todo?
            // this.httpService.triggerSearch();
            // this.ngOnInit();

        });

        //
        // subscribe to search data changing
        //
        this.httpService.mDataChanged.subscribe(data => {
            this.mLocalData = data;
        });

        this.subDateFromSearch = this.searchService.eeDatechange.subscribe(() => {
            this.setLocalDisplayDate();
        });

        this.httpService.bSearchingChanged.subscribe((bSearching) => {
            setTimeout(() => {
                this.bSearchServiceSearching = bSearching
            }, 1)
        })


    }


    ngOnInit() {
        this.searchService.iPage = 1;
        //
        // get url vars
        //

        this.route.params.subscribe((param) => {
            let sNewSearchMode = param['searchmode'];

            if(sNewSearchMode != this.searchService.sSearchMode)
            {
                // changed search mode, from one search mode to another
                this.searchService.sSearchMode = sNewSearchMode;

                if(typeof this.searchService.sSearchMode !== "undefined")
                {
                    this.changedSearchPage(this.searchService.sSearchMode);
                }else{
                    // why is it undefined? because we're on home..?
                    this.searchService.sSearchMode = 'default';
                }
            }
        });

        //
        // listen to event
        //
        this.subQueryChanged = this.searchService.queryChanged.subscribe((query) => {
            this.mQuery = query;
        });

        this.subLightboxOpen = this.searchService.eeThumbClick.subscribe(data => {
            this.bLightboxOpen = true;
        });

        this.subLightboxClose = this.searchService.eeLightboxClose.subscribe(data => {
            this.bLightboxOpen = false;
        });


        
    }

    changedSearchPage(sNewPage)
    {
        /*
        This method is called when the user changes to a search page, this can be from the user-page constructor (from another component route), or on param change (one user-page to another). 
        /{userId}/search
        /{userId}/folders
        /{userId}/map
        /{userId}/calendar

        this method is called from the contructor and ngOnInit methods of this component.
        */
        // don't trigger search, let each page do something itself
        
        switch(sNewPage)
        {
            case 'search':
            case 'folders':
                this.httpService.triggerSearch();
                break;
        }
        // this.httpService.triggerSearch();
    }

    ngOnDestroy()
    {
        this.subQueryChanged.unsubscribe();
        this.subLightboxOpen.unsubscribe();
        this.subLightboxClose.unsubscribe();
        this.subDateFromUrl.unsubscribe();
    }

    removeFilter(iIndex)
    {
        this.searchService.removeFilter(iIndex);
        this.httpService.triggerSearch();
    }
    clearFilters()
    {
        this.searchService.clearFilters();
        this.httpService.triggerSearch();
    }

    keydown(event)
    {
        if(event.keyCode === 13 && !this.searchService.bEmptyQuery())
        {
            this.directSearch();
        }
    }

    eTextQueryChange(newValue) {
        clearTimeout(this.tSearchEventTimeout);

        this.tSearchEventTimeout = setTimeout(function(httpService, searchService){
            searchService.updateURLToVars();
            httpService.triggerSearch();
        }, 150, this.httpService, this.searchService);

    }
    directSearch()
    {
        this.searchService.updateURLToVars();
        this.httpService.triggerSearch();
    }

    clearTextInput()
    {
        this.searchService.mQuery.q = '';
        this.directSearch();
    }

    onSetCalendarSearchMode(sNewSearchMode)
    {
        this.searchService.sCalendarSearchMode = sNewSearchMode

        this.setMDDateToStartOfUnit(this.searchService.sCalendarSearchMode)

        // do search?
        this.setLocalDisplayDate().then(() => {
            this.searchService.addSetCalendarFilter(
                this.searchService.sCalendarSearchMode,
                this.sCurrentDateDisplay,
                this.searchService.sDate
            )

            this.httpService.triggerSearch()
        })
    }


    //
    // calendar things
    //
    setMDDateToStartOfUnit(sMode) {
        switch(this.searchService.sCalendarSearchMode)
        {
            case 'day':
                this.searchService.mdDate.startOf(sMode);
                // sSearchDisplay =
                break;
            case 'week':
                this.searchService.mdDate.startOf(sMode);
                // add a week
                break;
            case 'month':
                this.searchService.mdDate.startOf(sMode);
                // add a month
                break;
            case 'year':
                this.searchService.mdDate.startOf(sMode);
                // add a year
                break;
        }
        this.setLocalDisplayDate();
    }
    onCalMove(iUnit)
    {
        this.setMDDateToStartOfUnit(this.searchService.sCalendarSearchMode)
        // depending on mode add a certain amount of time, then do new search
        // let sSearchDisplay = '';
        switch(this.searchService.sCalendarSearchMode)
        {
            case 'day':
                this.searchService.setDate(this.searchService.mdDate.add(iUnit, 'days'))
                // sSearchDisplay =
                break;
            case 'week':
                this.searchService.setDate(this.searchService.mdDate.add(iUnit, 'week'))
                // add a week
                break;
            case 'month':
                this.searchService.mdDate.add(iUnit, 'months')
                this.searchService.setDate(this.searchService.mdDate)
                // add a month
                break;
            case 'year':
                this.searchService.setDate(this.searchService.mdDate.add(iUnit, 'year'))
                // add a year
                break;
        }
        this.setLocalDisplayDate().then(() => {
            this.searchService.eeDatechange.emit();
            this.searchService.addSetCalendarFilter(
                this.searchService.sCalendarSearchMode,
                this.sCurrentDateDisplay,
                this.searchService.sDate
            );
            
            this.httpService.triggerSearch();
        });
    }

    //
    // people search
    //
    setPeopleSearchGender(sGender)
    {
        this.searchService.sPeopleSearchGender = sGender;
        this.searchService.removeFilterByType('people.gender');

        if(sGender !== 'both')
        {
            // add as filter
            this.searchService.addFilter('people.gender', this.searchService.sPeopleSearchGender + 'people', this.searchService.sPeopleSearchGender);
        }
        this.httpService.triggerSearch();

    }
    setPeopleSearchState(sState)
    {
        this.searchService.sPeopleSearchState = sState;
        this.searchService.removeFilterByType('people.state');

        if(sState !== 'all')
        {
            // add as filter
            this.searchService.addFilter('people.state', this.searchService.sPeopleSearchState + 'people', this.searchService.sPeopleSearchState);
        }
        this.httpService.triggerSearch();
    }
    setPeopleSearchGrouping(sGrouping)
    {
        this.searchService.sPeopleSearchGrouping = sGrouping;
        this.searchService.removeFilterByType('people.grouping');

        if(sGrouping !== 'any')
        {
            // add as filter
            this.searchService.addFilter('people.grouping', this.searchService.sPeopleSearchGrouping + 'people', this.searchService.sPeopleSearchGrouping);
        }
        this.httpService.triggerSearch();
    }

    setLocalDisplayDate()
    {
        return new Promise((resolve, reject) => {
            setTimeout(() => {
                let oDisplay = this.helperService.parseDisplayDates(
                    this.searchService.sCalendarSearchMode,
                    this.searchService.mdDate
                )
                this.sCurrentDateDisplay = oDisplay.sCurrentDateDisplay
                this.sCurrentHeaderDisplay = oDisplay.sDisplayHeader
                resolve()
            }, 1)
        });
    }

    /*
    parseDisplayDates()
    {
        this.searchService.sDate = this.searchService.mdDate.format('DD/MM/YYYY');

        switch(this.searchService.sCalendarSearchMode)
        {
            case 'day':
                this.sCurrentDateDisplay = this.searchService.mdDate.format('ddd Do');
                break;
            case 'week':
                this.sCurrentDateDisplay = 'Week ' + this.searchService.mdDate.format('w');
                break;
            case 'month':
                this.sCurrentDateDisplay = this.searchService.mdDate.format('MMM YYYY');
                break;
            case 'year':
                this.sCurrentDateDisplay = this.searchService.mdDate.format('YYYY');
                break;
        }
    }
    */

}
