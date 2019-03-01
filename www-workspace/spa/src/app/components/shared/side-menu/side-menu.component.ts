import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { AuthService, SearchService } from './../../../services';

@Component({
  selector: 'app-side-menu',
  templateUrl: './side-menu.component.html'
})
export class SideMenuComponent implements OnInit {

	private sCurrentPageUsername: string;

	private sQuery: string;

	private maMenuItems;


	constructor(
		private authService: AuthService,
		private searchService: SearchService,
		route: ActivatedRoute
	) {
		this.sCurrentPageUsername = route.snapshot.params['username'];

		this.maMenuItems = [
			{
				name: 'search',
				link: '/' + this.sCurrentPageUsername + '/search',
				icon: 'fa fa-th'
			},
			{
				name: 'folders',
				link: '/' + this.sCurrentPageUsername + '/folders',
				icon: 'fa fa-folder'
			},
			{
				name: 'map',
				link: '/' + this.sCurrentPageUsername + '/map',
				icon: 'fa fa-globe'
			},
			{
				name: 'calendar',
				link: '/' + this.sCurrentPageUsername + '/calendar',
				icon: 'fa fa-calendar'
			},
			{
				name: 'elevation',
				link: '/' + this.sCurrentPageUsername + '/elevation',
				icon: 'tachometer alternate icon'
			}/*,
            {
                name: 'colour',
                link: '/' + this.sCurrentPageUsername + '/colour',
                icon: 'fa fa-paint-brush'
            },
            {
                name: 'people',
                link: '/' + this.sCurrentPageUsername + '/people',
                icon: 'fa fa-users'
            }*/
		];
	}
  ngOnInit() {

		this.authService.authStatusChanged.subscribe(
			(mData) => {
				this.sCurrentPageUsername = mData.user;
			}
		);

		this.searchService.queryChanged
		.subscribe((query) => {
			this.sQuery = query;
		});

  }

	genLink(sPage) {
		// build a link to page with query if it's set
		let sRoute = sPage;

		if (typeof this.searchService.mQuery['q'] !== 'undefined') {
			if (this.searchService.mQuery['q'] !== '') {
				sRoute += ';q=' + this.searchService.mQuery['q'];
			}
		}

		return sRoute;
	}

}
