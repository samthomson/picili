import { Component, OnInit } from '@angular/core';
import { HttpService } from './../../../../services';

@Component({
  selector: 'app-user-page-link',
  templateUrl: './user-page-link.component.html'
})
export class UserPageLinkComponent implements OnInit {

    private sUsername: string = '';

    constructor(
        private httpService: HttpService
    ) { }

    ngOnInit() {

        this.httpService.getUser().subscribe(
            (mData) => {
                this.sUsername = mData.username;
            },
            (err) => {
                console.log('error getting user back in thing..')
            }
        );
    }
}
