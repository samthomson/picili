import { Component, Input } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { AuthService, HttpService, SearchService } from './../../../services';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html'
})
export class HeaderComponent{

    @Input() bEmpty: boolean = false;

    private sUsername: string = '';
    private bProcessing: boolean = false;
    private cProcessingTasks: number = 0;
    private cProcessingFiles: number = 0;

    private bAuthenticated: boolean = false;
    private bOnUserPage: boolean = false;
    private sCurrentPageUsername: string = 'giraffe';
    private bShowingUserDropdownMenu: boolean = false;

    constructor(
        private httpService: HttpService,
        private authService: AuthService,
        private searchService: SearchService,
        route: ActivatedRoute
    ) {
        this.sCurrentPageUsername = route.snapshot.params['username'];

        this.bOnUserPage = (typeof this.sCurrentPageUsername !== "undefined") ? true : false;

        this.bAuthenticated = this.authService.isLoggedIn();
        
        this.authService.authStatusChanged.subscribe(b => {
            this.bAuthenticated = b;
        });
    }

    ngOnInit() {
        /*
        3/6/17 - commented out so as not to make /me request on each page change, is it needed anywhere even?
        */

        // only call backend asking for username if we don't have it
        if (this.sUsername === '')
        {
            this.httpService.getUser().subscribe(
                (mData) => {
                    this.sUsername = mData.username;
                    this.bProcessing = mData.bProcessing;
                    this.cProcessingTasks = mData.cProcessing;
                    this.cProcessingFiles = mData.cFiles;
                },
                (err) => {
                    if (this.bAuthenticated) {
                        console.log('triggering logout')
                        this.bAuthenticated = false;
                        this.authService.logOut();
                    }
                }
            );
        }
    }

    onLogout(){
        this.authService.logOut();
    }
}
