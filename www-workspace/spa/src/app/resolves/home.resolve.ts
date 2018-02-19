import { Injectable } from '@angular/core';
import { Resolve, ActivatedRouteSnapshot } from '@angular/router';
import { HttpService } from './../services';

@Injectable()

export class HomeResolve implements Resolve<any> {

	constructor(
		private httpService: HttpService
	) {}

	resolve(route: ActivatedRouteSnapshot) {
		return this.httpService.getHomeAggs();
	}
}
