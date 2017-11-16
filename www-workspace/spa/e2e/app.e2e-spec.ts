import { Picili2SpaPage } from './app.po';

describe('picili-2-spa App', function() {
  let page: Picili2SpaPage;

  beforeEach(() => {
    page = new Picili2SpaPage();
  });

  it('should display message saying app works', () => {
    page.navigateTo();
    expect(page.getParagraphText()).toEqual('app works!');
  });
});
