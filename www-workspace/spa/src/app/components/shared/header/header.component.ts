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

        // console.log('header name: ' + this.sCurrentPageUsername);
        this.bOnUserPage = (typeof this.sCurrentPageUsername !== "undefined") ? true : false;

        this.bAuthenticated = this.authService.isLoggedIn();
        
        this.authService.authStatusChanged.subscribe(b => {
            this.bAuthenticated = b;
        });
    }

    ngOnInit() {
        /*
        3/6/17 - commented out so as now to make /me request on each page change, is it needed anywhere even?
        */

        // console.log('init header, call get username, currently it is: '+ this.sUsername);
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
                    console.log('expired token?');
                    if (this.bAuthenticated) {
                        this.bAuthenticated = false;
                        this.authService.logOut();
                    }
                }
            );
        }
    }

    onLogout(){
        console.log('logout?')
        this.authService.logOut();
    }

}
