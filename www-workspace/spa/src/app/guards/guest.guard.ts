
import { Injectable } from '@angular/core';
import { ActivatedRoute, CanActivate, Router } from '@angular/router';
import { AuthService } from './../services';

@Injectable()
export class GuestGuard implements CanActivate {

  constructor(
	private router: Router,
	private authService: AuthService,
	private route: ActivatedRoute
  ) {}

  canActivate() {

	if (!this.authService.isLoggedIn()) {
		// not logged in so return true
		return true;
	}

	// logged in so redirect to their page
	const sRelativeRoute: string = '/' + this.route.snapshot.params['username']
	this.router.navigate([sRelativeRoute]);
	return false;
  }
}
