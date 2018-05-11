import { Injectable } from '@angular/core';
import { GlobalVars } from './../../env';
import { SearchService } from './search.service'

@Injectable()
export class HelperService {

    constructor(
        private gbl: GlobalVars,
        private searchService: SearchService,
    ) {}

    parseDisplayDates(sCalendarSearchMode, mdDate)
    {
        let sDate = mdDate.format('DD/MM/YYYY')
        let sCurrentDateDisplay = 'giraffe'
        let sDisplayHeader = 'tomato'

        switch(sCalendarSearchMode)
        {
            case 'day':
                sCurrentDateDisplay = mdDate.format('ddd Do');
                sDisplayHeader = mdDate.format('ddd Do MMM YYYY');
                break;
            case 'week':
                sCurrentDateDisplay = 'Week ' + mdDate.format('w');
                sDisplayHeader = `Week ${mdDate.format('w')} ${mdDate.format('MMM')} ${mdDate.format('YYYY')}`;
                break;
            case 'month':
                sCurrentDateDisplay = mdDate.format('MMM YYYY');
                sDisplayHeader = mdDate.format('MMMM YYYY');
                break;
            case 'year':
                sCurrentDateDisplay = mdDate.format('YYYY');
                sDisplayHeader = mdDate.format('YYYY');
                break;
        }

        return {
            sCurrentDateDisplay: sCurrentDateDisplay,
            sDisplayHeader: sDisplayHeader
        }
    }

    thumbUrl(sSize, id) {
        return this.gbl.awsBucketUrl + this.gbl.sCurrentPageUsername +'/' + sSize + id+'.jpg'
    }

    getBaseRouterLink(sPage) {
        const sUserName = this.gbl.sCurrentPageUsername
        return `/${sUserName}/${sPage}/`
    }

    getQVarsWithNewQuery(sType, sDisplay, sValue) {

        // this should not modify the existing query state, it just creates a read only query to be displayed as a link

        let oReadOnlyQVars = this.searchService.getQVars()
        if(typeof oReadOnlyQVars['filters'] === 'undefined') {
            oReadOnlyQVars['filters'] = []
        } else {
            oReadOnlyQVars['filters'] = JSON.parse(oReadOnlyQVars['filters'])
        }
        switch (sType) {
            case 'calendar':
                oReadOnlyQVars = this.searchService.removeFilterByTypeOnQueryObject(oReadOnlyQVars, 'calendar')
                break
        }
        oReadOnlyQVars['filters'].push({'type': sType, 'display': sDisplay, 'value': sValue})
        oReadOnlyQVars['filters'] = JSON.stringify(oReadOnlyQVars['filters'])

        return oReadOnlyQVars
    }

    getRawQueryVarsWithNewQuery(sQuery: string) {
        let oQVars = this.searchService.getQVars()
        oQVars['q'] = sQuery

        return oQVars
    }
}
