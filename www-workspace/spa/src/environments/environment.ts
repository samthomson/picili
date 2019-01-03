// The file contents for the current environment will overwrite these during build.
// The build system defaults to the dev environment which uses `environment.ts`, but if you do
// `ng build --env=prod` then `environment.prod.ts` will be used instead.
// The list of which env maps to which file can be found in `angular-cli.json`.

export const environment = {
  production: false,
  baseURL: 'http://localhost',
  sAPIBaseUrl: window.location.protocol + '//' + window.location.hostname + (window.location.port ? ':' + window.location.port : ''),
  awsBucketUrl: 'https://s3-eu-west-1.amazonaws.com/picili-bucket/t/'
};
