import { environment } from './environments/environment';

export class GlobalVars {
	// sAPIBaseUrl: string = 'http://picili-user-api.dev';
	sAPIBaseUrl: string = environment.sAPIBaseUrl;
	sOAUTHAPIBaseUrl: string = environment.sAPIBaseUrl;
	sCurrentPageUsername: string = null;
	sAuthTokenName: string = 'picili.auth_token';
	sAuthId: string = 'picili.user_id';
	iResizeTimeout: number = 200;
	awsBucketUrl: string = `https://s3-${process.env.REGION}.amazonaws.com/${process.env.BUCKET}'/t/`
}
