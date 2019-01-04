import { Component, OnInit, Input } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { SearchService } from './../../../../services';

@Component({
  selector: 'app-search',
  templateUrl: './search.component.html'
})
export class SearchComponent implements OnInit {

	private oAppPageState: any;

	@Input() results;


	constructor(
		private searchService: SearchService,
		private route: ActivatedRoute
	) {
		this.oAppPageState = this.route.snapshot.data['userPageState'];
	}

	ngOnInit() {
	}

}
