import { Component, OnInit, AfterViewInit, Injector  } from '@angular/core';

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

    constructor(
        private searchService: SearchService,
        private httpService: HttpService,
        private helperService: HelperService,
        private gbl: GlobalVars/*,
        private inj:Injector*/
    ) {
        console.log('\n\nCALENAR CONSTRUCTOR\n\n')
        //this.parentComponent = this.inj.get(UserPageComponent);
    }

    setResults(oResults) {
        // console.log("setting results: ", oResults)
        this.oDateResults = oResults
    }

    goToDate(sDate) {
        console.log('go to day: ', sDate)
        // EMIT TO PARENT HERE
        //// this.parentComponent.parseDisplayDates()
        // change to day mode
        this.searchService.sCalendarSearchMode = 'day'

        // set date
        this.searchService.setDate(moment(sDate));

        this.searchService.addSetCalendarFilter(this.searchService.sCalendarSearchMode, this.searchService.mdDate.format('ddd Do'), this.searchService.sDate);


        // trigger search
        this.httpService.triggerSearch();
        this.searchService.eeDatechange.emit();
    }

    /*
    ngAfterViewInit()
    {
        this.searchService.eeDatechange.emit();
    }
    */

    formatWeekDateHeader(sDate)
    {
        // turn '2016-06-21' into 21st June 2016
        return moment(sDate).format('dddd Do');
    }


    ngOnInit()
    {
        console.log('\n\nCALENAR ONINIT\n\n')
        let bCalVarsInUrl = this.searchService.determineLocalVarsByParsingUrlVars()

        if(!bCalVarsInUrl) {
            // no cal vars in url, lets set defaults and update ui before searching
            // calender search vars
                this.searchService.sCalendarSearchMode = 'month';
                this.searchService.mdDate = moment().startOf('month');
                this.searchService.sDate = this.searchService.mdDate.format('DD/MM/YYYY');
                // console.log('\n\n\neeDatechange\n\n\n')
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
                console.log('\n\n-- not found in url, use defaults')
        }else{
            this.httpService.triggerSearch();
        }
        console.log('heard back from determine search vars, trigger search next')
        // this.httpService.triggerSearch();

        // don't do this, it will overload url cal search  this.goToDate(moment())
        //this.searchService.eeDatechange.emit();
        //// this.parentComponent.parseDisplayDates()
        // this.searchService.eeDatechange.emit();
        // EMIT TO PARENT HERE

        this.httpService.bSearchingChanged.subscribe((bSearching) => {
            // search started or ended, if started, clear calendar
            if(bSearching) {
                this.aAvailableDates = []
                this.oDateResults = null
            }
        })

        this.httpService.mDataChanged.subscribe(() => {
            // console.log('calendar has noticed a search has been completed');
            // calculate available days, to be used in the UI

            // populate an array of dates, for the current period. Where the period is either a week or a month. and use actual dates starting with the set date
            // searchService.sCalendarSearchMode
            // this.searchService.mdDate
            // console.log(this.searchService.sCalendarSearchMode)
            var mdDateCopy = this.searchService.mdDate.clone()
            this.aAvailableDates = []
            let oLocalDateResults: any = []

            

            if
            (
                typeof this.searchService.mData.search !== 'undefined' &&
                typeof this.searchService.mData.search.aggs !== 'undefined'
            )
            {
                switch(this.searchService.sCalendarSearchMode)
                {
                    case 'week':
                        for (var iDayOfWeekCount = 0; iDayOfWeekCount < 7; iDayOfWeekCount++)
                        {
                            this.aAvailableDates.push(this.searchService.mdDate.clone().add(iDayOfWeekCount, 'days').format('YYYY-MM-DD'))
                        }
                        break;
                    case 'month':
                        for (var iDayOfMonthCount = 0; iDayOfMonthCount < this.searchService.mdDate.daysInMonth(); iDayOfMonthCount++)
                        {
                            this.aAvailableDates.push(this.searchService.mdDate.clone().add(iDayOfMonthCount, 'days').format('YYYY-MM-DD'))
                        }
                        break;
                }

                // then parse in actual day results
                for (var iDays = 0; iDays < this.aAvailableDates.length; iDays++) {
                    // look for day-result, add it, or add empty
                    var bFoundSomething = false;

                    // search for an aggregation of files for this date
                    var sKey = '"' + this.aAvailableDates[iDays] + '"'
                    for (var iDayResults = 0; iDayResults < this.searchService.mData.search.aggs.length && !bFoundSomething; iDayResults++) {

                        if (this.searchService.mData.search.aggs[iDayResults].name.startsWith(this.aAvailableDates[iDays])) {
                            bFoundSomething = true

                            oLocalDateResults.push( this.searchService.mData.search.aggs[iDayResults]
                            )
                        }
                    }

                    if(!bFoundSomething) {
                        oLocalDateResults.push({})
                    }
                }
                // we have built an object of dates, some containing a result agg object

                this.oDateResults = oLocalDateResults
            }
        });
    }
}
