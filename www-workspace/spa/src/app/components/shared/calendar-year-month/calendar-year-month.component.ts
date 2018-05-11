import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { HttpService, SearchService } from './../../../services';

import * as moment from 'moment'

@Component({
  selector: 'calendar-year-month',
  templateUrl: './calendar-year-month.component.html'
})
export class CalendarYearMonthComponent implements OnInit {

    results: any = []
    @Input() month: string = '';
    @Input() oPictureData: any;
    sFormattedMonthHeader: string = ''

    mdFirstOfThisMonth

    aParsedDates = []
    aDisplayDates = []

    @Output() dateClicked = new EventEmitter<any>()
    @Output() monthClicked = new EventEmitter<any>()

    constructor(
      private searchService: SearchService,
      private httpService: HttpService
    ) { 
    }

    ngOnInit() {
      this.sFormattedMonthHeader = moment(this.month, 'YYYY-MM').format('MMM')
      this.mdFirstOfThisMonth = moment(this.month, 'YYYY-MM')
      const iDaysInMonth = this.mdFirstOfThisMonth.daysInMonth()

      this.aParsedDates = []

      for (let iDate = 0; iDate < this.oPictureData.length; iDate++) {
        this.aParsedDates[this.oPictureData[iDate].dateKey] = this.oPictureData[iDate]
      }

      for (let cDay = 0; cDay < iDaysInMonth; cDay++) {
        let iCount = 0
        let sID = null

        let sKey:any = cDay + 1
        if (sKey < 10) {
          sKey = '0' + String(sKey)
        } else {
          sKey = String(sKey)
        }

        if (typeof this.aParsedDates[sKey] !== "undefined") {
          iCount = this.aParsedDates[sKey].count
          sID = this.aParsedDates[sKey].sImageID
        }

        this.aDisplayDates.push({
          sDate: this.mdFirstOfThisMonth.clone().add(cDay, 'days').format('D'),
          oDate: this.mdFirstOfThisMonth.clone().add(cDay, 'days'),
          count: iCount,
          sID
        })
      }
    }

    eDateClicked(oDate) {
      this.dateClicked.emit({
        oDate
      })
    }

    eMonthClicked(oDate) {
      this.monthClicked.emit({
        oDate
      })
    }
}
