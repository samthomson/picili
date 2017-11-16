import { Injectable, EventEmitter } from '@angular/core';
import { Http, Headers, Response, URLSearchParams, RequestOptions } from '@angular/http';
import { Router, CanActivate } from '@angular/router';

import { Observable } from 'rxjs/Rx';
import 'rxjs/add/operator/map';

import { GlobalVars } from './../../env';

@Injectable()
export class AuthService {


    authStatus: boolean = false;

    authStatusChanged = new EventEmitter<any>();

    sToken: string;

    
    // authTokenChanged = new EventEmitter<string>();
    //
    // jUser: any;
    // userChanged = new EventEmitter<any>();



    constructor(
        private http: Http,
        private router: Router,
        private gbl: GlobalVars
    ) {
        this.authStatus = !!localStorage.getItem(this.gbl.sAuthTokenName);

        if(this.authStatus) {
			this.sToken = localStorage.getItem(this.gbl.sAuthTokenName);
		}
    }

    isLoggedIn() {
        return this.authStatus;
    }

    attemptLogin(sEmail, sPassword): Observable<any>
    {
        let jAuthParams = new URLSearchParams();
        jAuthParams.set('email', sEmail);
        jAuthParams.set('password', sPassword);

        let headers = new Headers();
		headers.append('Content-Type', 'application/x-www-form-urlencoded');

        let options = new RequestOptions(
            {
                headers: headers,
                withCredentials: false
            }
        );


        return this.http.post(
            this.gbl.sAPIBaseUrl + '/app/authenticate',
            jAuthParams.toString(),
            options
        )
            .map(
                (response: Response) => {

                    let data = response.json();

                    let token = data.token;
                    let authStatus = data.success;
                    let user = data.username;

                    if (authStatus && token && user) {
                        // set token property
                        this.sToken = token;

                        // store username and jwt token in local storage to keep user logged in between page refreshes
                        localStorage.setItem(this.gbl.sAuthTokenName, token);

                        //console.log("set token as: " + localStorage.getItem('currentUser'));

                        this.authStatus = authStatus;
                        this.authStatusChanged.emit({'authed': true, 'user' : user});

                        this.sToken = token;

                        // return true to indicate successful login
                        return {'success': true, 'user': user};
                    }else{
                        // return false to indicate failed login
                        return {'success': false};
                    }
            });
    }

    attemptRegister(sUsername, sEmail, sPassword): Observable<any>
    {
        let jAuthParams = new URLSearchParams();
        jAuthParams.set('username', sUsername);
        jAuthParams.set('email', sEmail);
        jAuthParams.set('password', sPassword);

        let headers = new Headers();
		headers.append('Content-Type', 'application/x-www-form-urlencoded');

        let options = new RequestOptions({ headers: headers, withCredentials: false });

        return this.http.post(
            this.gbl.sAPIBaseUrl + '/app/register',
            jAuthParams.toString(),
            options
        )
            .map(
                (response: Response) => {
                    let data = response.json();

                    let token = data.token;
                    let bSuccess = data.success;
                    let user = data.username;

                    if(!bSuccess) {
                        return {'successful': false, 'errors': data.errors};
                    }

                    if (bSuccess && token) {
                        // set token property
                        this.sToken = token;

                        // store username and jwt token in local storage to keep user logged in between page refreshes
                        localStorage.setItem(this.gbl.sAuthTokenName, token);

                        //console.log("set token as: " + localStorage.getItem('currentUser'));

                        this.authStatus = bSuccess;
                        // this.authStatusChanged.emit(this.authStatus);

                        this.sToken = token;
                        // this.authTokenChanged.emit(this.sToken);

                        // return true to indicate successful register & login
                        return {'successful': true, 'user': user};
                    }else{
                        // return false to indicate failed register
                        return {'successful': false, 'errors': [{'unknown': 'unknown error'}]};
                    }
                }
            );

    }

    getToken()
    {
        return localStorage.getItem(this.gbl.sAuthTokenName);
    }

    logOut()
    {
        localStorage.removeItem(this.gbl.sAuthTokenName);
        this.authStatus = false;
        this.authStatusChanged.emit(this.authStatus);
        // 'logout' the user (delete their local token and redirect them)

        this.router.navigate(['/']);
    }
}
