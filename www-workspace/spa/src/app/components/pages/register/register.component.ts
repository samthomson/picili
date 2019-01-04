import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../../services';

@Component({
  selector: 'app-register',
  templateUrl: './register.component.html'
})
export class RegisterComponent {

	private registerUsername: string = '';
	private registerEmail: string = '';
	private registerPassword: string = '';

	private bAttemptingRegister: boolean = false;

	private errors: string[] = [];
	private bRegisterFailed: boolean = false;

	constructor(
		private router: Router,
		private authService: AuthService
	) { }

	onRegisterSubmit(frmRegister) {

		this.bAttemptingRegister = true;
		this.bRegisterFailed = false;
		this.errors = [];

		this.authService.attemptRegister(
			this.registerEmail,
			this.registerPassword
		)
			.subscribe(
				result => {
					this.bAttemptingRegister = false;
					if (result.successful === true) {
						// register successful
						this.bRegisterFailed = false;
						this.errors = [];
						this.router.navigate(['/' + result.user]);
					} else {
						this.bRegisterFailed = true;
						this.errors = result.errors;
					}
				}
			);
	}

}
