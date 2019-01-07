import { environment } from './environments/environment';

export class GlobalVars {
	sAPIBaseUrl: string = environment.sAPIBaseUrl;
	sOAUTHAPIBaseUrl: string = environment.sAPIBaseUrl;
	sCurrentPageUsername: string = null;
	sAuthTokenName: string = 'picili.auth_token';
	sAuthId: string = 'picili.user_id';
	iResizeTimeout: number = 200;
	awsBucketUrl: string = environment.awsBucketUrl
}
