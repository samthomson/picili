import { HelperService } from './../../../services/helper.service';
import {
    Component,
    Input,
    ElementRef,
    ViewChild,
    NgZone,
    OnInit
} from '@angular/core';
import { SearchService, HttpService } from './../../../services';
import { GlobalVars } from './../../../../env';

@Component({
  selector: 'app-result-grid',
  templateUrl: './result-grid.component.html'
})
export class ResultGridComponent implements OnInit {

    results;
    bSearching: boolean = false;
    @Input() sDisplayMode: string = 'justified'

    aJustifiedRows = [];
    aJustifiedRowHeights = [];
   
    @ViewChild('availableWidth') availableWidth: ElementRef;

    resizeId;

    constructor(
        private searchService: SearchService,
        private httpService: HttpService,
        private helperService: HelperService,
        private gbl: GlobalVars,
        private el:ElementRef,
        private ngZone: NgZone
    ) {
        this.httpService.bSearchingChanged.subscribe((data) =>{
            // console.log("b searching changed, searching: " + data);
            this.bSearching = data;
        });

        window.onresize = (e) =>
        {
            //ngZone.run will help to run change detection
            this.ngZone.run(() => {
                
                clearTimeout(this.resizeId);
                this.resizeId = setTimeout(() => {
                    // console.log("Width: " + window.innerWidth);
                    // console.log("Height: " + window.innerHeight);
                    // console.log('\n\n\nWINDOW RESIZE: DO JUSTIFY GALLERY\n');
                    this.calculateJustifiedGallery();
                }, this.gbl.iResizeTimeout);
            });
        };
    }

    ngOnInit() {
        // console.log('result grid on init', this.results)
        if (typeof this.results !== "undefined") {
            // console.log('results wasn\'t null, so calculating grid')
            this.calculateJustifiedGallery();
        }else{
            // subscribe to when there are results
            // console.log('subscribe to when there are results')
            this.httpService.mDataChanged.subscribe((data) =>{
                // console.log("mDataChanged changed, mDataChanged: " + data);
                this.results = data.search.results;

                // console.log('\n\n\nSEARCH DATA CHANGED: DO JUSTIFY GALLERY\n');
                this.calculateJustifiedGallery();

            });

            // console.log('result grid on it, undefined results, set from search service if there are results')
            if (this.searchService.bHasSearchResults) {
                this.results = this.searchService.mData.search.results
                this.calculateJustifiedGallery()
            }
        }
    }

    resultThumbClick(i)
    {
        // console.log("thumb: " + i);
        this.searchService.eThumbClick(i);
    }

    calculateJustifiedGallery()
    {
        if (this.sDisplayMode === 'justified')
        {
            // go through each image, adding to temp line collection, adding widths until passed contained width
            let aRows = [];
            let aTempRow = [];
            let aiRowHeights = [];

            let iRunningWidth = 0;
            let iImagesInRow = 0;
            let iMargin = 8;
            let iScrollMargin = 24;
            let iBaseRowHeight = 300;
            let iCurrentRowHeight = iBaseRowHeight;


            // get container width
            let iAvailableWidth = this.availableWidth.nativeElement.offsetWidth - iScrollMargin;
            // console.log('available width: ', iAvailableWidth)

            for (let iResult = 0; iResult < this.results.length; iResult++)
            {
                // console.log('checking image ', iResult);
                this.results[iResult].index = iResult
                aTempRow.push(this.results[iResult]);
                // console.log("added: ", this.results[iResult])
                iImagesInRow++;

                // calculate prospective width
                // all images in row plus this one, sized to the shortest


                // get shortest in row
                // take the first height as a base
                let iShortest = aTempRow[0].m_h;
                for (let iRowHeightCheck = 1; iRowHeightCheck < aTempRow.length; iRowHeightCheck++) {
                    if (aTempRow[iRowHeightCheck].m_h < iShortest) {
                        iShortest = aTempRow[iRowHeightCheck].m_h;
                    }
                    // console.log('shortest in row is ' + iShortest);
                }
                // scale each to that height
                iRunningWidth = 0;
                for (let iScaleEachInRow = 0; iScaleEachInRow < aTempRow.length; iScaleEachInRow++) {
                    let fScale = iShortest / aTempRow[iScaleEachInRow].m_h;
                    let iScaledHeight = aTempRow[iScaleEachInRow].m_h * fScale;
                    let iScaledWidth = aTempRow[iScaleEachInRow].m_w * fScale;

                    aTempRow[iScaleEachInRow].s_h = iScaledHeight;
                    aTempRow[iScaleEachInRow].s_w = iScaledWidth;

                    iRunningWidth += iScaledWidth;

                    // console.log(`scale ${aTempRow[iScaleEachInRow].m_w} by ${fScale}, which is ${iScaledWidth}, and now img is ${aTempRow[iScaleEachInRow].s_w}, now running width is ${iRunningWidth}`);
                }
                // console.log('after scaling each in row, running width is ', iRunningWidth);

                // iRunningWidth += this.results[iResult].s_w;
                // if the current image is shorter than the current row, lower the current row height
                // if (this.results[iResult].m_h < iCurrentRowHeight) {
                //     console.log('adjusting base height to: ' + this.results[iResult].m_h);
                //     iCurrentRowHeight = this.results[iResult].m_h;
                // }

                // when over limit, calculate scaling factor, and add to structure of rows
                let iRunningMarginWidth = (iMargin * (iImagesInRow-1));
                let iRunningWidthIncludingMargins = iRunningWidth + iRunningMarginWidth;

                if (iRunningWidth > iAvailableWidth - iRunningMarginWidth) {
                    // console.log('-- overflowing --');
                    let iOversizedRatio = iRunningWidth / (iAvailableWidth - iRunningMarginWidth);
                    // console.log("iOversizedRatio: ", iOversizedRatio)

                    

                    let iRowHeight = iShortest / iOversizedRatio;

                    for (let iFinalScaleEachInRow = 0; iFinalScaleEachInRow < aTempRow.length; iFinalScaleEachInRow++) {

                        aTempRow[iFinalScaleEachInRow].s_h = aTempRow[iFinalScaleEachInRow].s_h / iOversizedRatio;
                        aTempRow[iFinalScaleEachInRow].s_w = aTempRow[iFinalScaleEachInRow].s_w / iOversizedRatio;
                    }
                    // add all to row and reset
                    aRows.push(aTempRow);

                    aTempRow = [];
                    iRunningWidth = 0;
                    iImagesInRow = 0;
                    iCurrentRowHeight = iBaseRowHeight;

                    aiRowHeights.push(iRowHeight);
                    // console.log("heights now: ", aiRowHeights)
                }else{ 
                    // todo: put left over images into a row somehow? or squeeze into previous?
                    if(iResult === (this.results.length - 1)) {
                        // console.log('at the end, not overflowing, slot in')
                        // we're at the end
                        aRows.push(aTempRow);
                        // to do, not 300 but it's actual height
                        aiRowHeights.push(aTempRow[0].s_h); // default                    
                    }
                }
            }

            this.aJustifiedRows = aRows;
            this.aJustifiedRowHeights = aiRowHeights;
        }
    }


    showMore()
    {
        this.searchService.iPage++;
        this.httpService.triggerSearch(false).then(() => {
        })
    }
}
