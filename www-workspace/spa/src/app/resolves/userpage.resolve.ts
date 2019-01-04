import { Injectable } from '@angular/core';
import { Resolve, ActivatedRouteSnapshot } from '@angular/router';
import { HttpService } from './../services';

@Injectable()

export class UserPageResolve implements Resolve<any> {

	constructor(
		private httpService: HttpService
	) {}

	resolve(route: ActivatedRouteSnapshot) {
		// if there's a search query send that along too..
		// return this.httpService.fetchPageState(route.params['username']);
		return null;
	}
}
