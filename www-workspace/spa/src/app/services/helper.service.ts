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
        };
    }

    thumbUrl(sSize, id) {
        return this.gbl.awsBucketUrl + this.gbl.sCurrentPageUsername +'/' + sSize + id+'.jpg'
    }

    getBaseRouterLink(sPage) {
        const sUserName = this.gbl.sCurrentPageUsername
        return `/${sUserName}/${sPage}/`
    }

    getQVarsWithNewQuery(sType, sDisplay, sValue) {
        let oQVars = this.searchService.getQVars()
        if(typeof oQVars['filters'] === 'undefined') {
            oQVars['filters'] = []
        }
        oQVars['filters'].push({'type': sType, 'display': sDisplay, 'value': sValue})
        oQVars['filters'] = JSON.stringify(oQVars['filters'])

        return oQVars
    }

    getRawQueryVarsWithNewQuery(sQuery: string) {
        let oQVars = this.searchService.getQVars()
        oQVars['q'] = sQuery

        return oQVars
    }
}
