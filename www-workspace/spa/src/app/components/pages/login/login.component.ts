import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../../services';

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html'
})
export class LoginComponent {

	private loginEmail: string = '';
	private loginPassword: string = '';

	private bAttemptingLogin: boolean = false;
	private bLoginFailed: boolean = false;

	private error = '';

	constructor(
		private router: Router,
		private authService: AuthService
	) { }

	onSubmit(f) {

		this.bAttemptingLogin = true;
		this.bLoginFailed = false;
		this.error = '';

		this.authService.attemptLogin(this.loginEmail, this.loginPassword)
			.subscribe(
				result => {
					this.bAttemptingLogin = false;
					if (result.success === true) {
						// login successful
						this.router.navigate(['/' + result.user]);
					} else {
						this.bLoginFailed = true;

						this.error = 'Email or password is incorrect';
					}
				},
				err => {
					this.bAttemptingLogin = false;
				}
			);
		}

}
