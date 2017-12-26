import { environment } from './environments/environment';

export class GlobalVars {
    // sAPIBaseUrl: string = 'http://picili-user-api.dev';
    sAPIBaseUrl: string = environment.sAPIBaseUrl;
    sOAUTHAPIBaseUrl: string = environment.baseURL;
    sCurrentPageUsername: string = null;
    sAuthTokenName: string = 'picili.auth_token';
    iResizeTimeout: number = 200;
    awsBucketUrl: string = environment.awsBucketUrl;
}
