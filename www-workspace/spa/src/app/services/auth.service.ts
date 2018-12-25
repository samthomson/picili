import { Injectable, EventEmitter } from '@angular/core';
import { Response, URLSearchParams, RequestOptions } from '@angular/http';
import { Router, CanActivate } from '@angular/router';
import { Headers } from '@angular/http';

import { HttpClientModule, HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';

import { Observable } from 'rxjs/Rx';
import 'rxjs/add/operator/map';
import { map } from 'rxjs/operators';
import 'rxjs/Rx';

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
        private http: HttpClient,
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
        let jAuthParams = new HttpParams()
            .set('email', sEmail)
            .set('password', sPassword);

        let headers = new HttpHeaders();
        headers.append('Content-Type', 'application/x-www-form-urlencoded');
        
        console.log('email: ', sEmail)
        console.log('params: ', jAuthParams.toString())

        let options = 
            {
                headers: headers,
                withCredentials: false,
                params: jAuthParams
            }
        

        // .pipe(map((response: any) => response.json()));
        return this.http.post(
            this.gbl.sAPIBaseUrl + '/app/authenticate',
            { params: jAuthParams },
            options
        )
            .pipe(
                map((response: any) => {

                    let data = response;

                    let token = data.token;
                    let authStatus = data.success;
                    let user = data.username;

                    if (authStatus && token && user) {
                        // set token property
                        this.sToken = token;

                        // store username and jwt token in local storage to keep user logged in between page refreshes
                        localStorage.setItem(this.gbl.sAuthTokenName, token);

                        this.authStatus = authStatus;
                        this.authStatusChanged.emit({'authed': true, 'user' : user});

                        this.sToken = token;
                        
                        // return true to indicate successful login
                        return {'success': true, 'user': user};
                    }else{
                        // return false to indicate failed login
                        return {'success': false};
                    }
            }))
            .catch((error:any) => Observable.throw(console.log('error authenticating: ', error.message)));
    }

    attemptRegister(sUsername, sEmail, sPassword): Observable<any>
    {
        let jAuthParams = new HttpParams()
            .set('email', sEmail)
            .set('password', sPassword);

        let headers = new HttpHeaders()
            .append('Content-Type', 'application/x-www-form-urlencoded');

        let options = { headers: headers, withCredentials: false, params: jAuthParams };

        return this.http.post(
            this.gbl.sAPIBaseUrl + '/app/register',
            { params: jAuthParams },
            options
        )
            .map(
                (response: any) => {
                    let data = response;

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
        console.log('log out?')
        localStorage.removeItem(this.gbl.sAuthTokenName);
        this.authStatus = false;
        this.authStatusChanged.emit(this.authStatus);
        // 'logout' the user (delete their local token and redirect them)

        this.router.navigate(['/']);
    }
}
