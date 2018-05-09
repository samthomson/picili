import { Component, Input } from '@angular/core';

@Component({
  selector: 'calendar-year-month',
  templateUrl: './calendar-year-month.component.html'
})
export class CalendarYearMonthComponent{

    @Input() month: string = '';


    constructor(
    ) {
    }

}
