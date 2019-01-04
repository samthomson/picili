import { Injectable } from '@angular/core';
import { Router, CanActivate } from '@angular/router';
import { AuthService } from '../services';
// import { Observable } from 'rxjs/Observable';

@Injectable()
export class AuthGuard implements CanActivate {

	constructor(
		private router: Router,
		private authService: AuthService
	) { }

	canActivate() {
		if (this.authService.isLoggedIn()) {
			// logged in so return true
			// return Observable.of(true);
			return true;
		}

		// not logged in so redirect to login page
		this.router.navigate(['/login']);
		return false;

	}
}
