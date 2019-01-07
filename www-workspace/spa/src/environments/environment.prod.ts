export const environment = {
  production: true,
  baseURL: 'https://test.picili.com',
  sAPIBaseUrl: window.location.protocol + '//' + window.location.hostname + (window.location.port ? ':' + window.location.port : ''),
  awsBucketUrl: `https://s3-ap-southeast-1.amazonaws.com/picili-test/t/`
};
