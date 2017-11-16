import { Pipe, PipeTransform } from '@angular/core';
import { SearchService } from './../../services';


@Pipe({name: 'calendarPeriod', pure: false})
export class CalendarPeriodPipe implements PipeTransform {

    constructor(public searchService: SearchService) { }
    
    transform(value: any): any {

        let sReturn = '';

        if(typeof value !== 'undefined')
        {
            switch(this.searchService.sCalendarSearchMode)
            {
                case 'day':
                    sReturn = value.format('ddd Do');
                    break;
                case 'week':
                    sReturn = 'Week ' + value.format('w');
                    break;
                case 'month':
                    sReturn = value.format('MMM YYYY');
                    break;
                case 'year':
                    sReturn = value.format('YYYY');
                    break;
            }
        }

        return sReturn
    }
}