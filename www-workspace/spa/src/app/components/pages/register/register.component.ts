import { Component } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../../services';

@Component({
  selector: 'app-register',
  templateUrl: './register.component.html'
})
export class RegisterComponent {

    constructor(
        private router: Router,
        private authService: AuthService
    ) { }

    private registerUsername: string = '';
    private registerEmail: string = '';
    private registerPassword: string = '';

    private bAttemptingRegister: boolean = false;

    private errors = [];

    onRegisterSubmit(frmRegister) {

      this.bAttemptingRegister = true;

      this.authService.attemptRegister(
          this.registerEmail,
          this.registerPassword
      )
          .subscribe(
              result => {
                  this.bAttemptingRegister = false;
                  if (result.successful === true) {
                      // register successful
                      this.router.navigate(['/' + result.user]);
                      this.errors = [];
                  }else{
                      this.bAttemptingRegister = false;
                      this.errors = result.errors;
                  }
              }
          );
    }

}
