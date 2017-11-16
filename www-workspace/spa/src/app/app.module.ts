import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { HttpModule } from '@angular/http';
import { RouterModule } from '@angular/router';

import { PiciliAppComponent } from './picili-app.component';
import {
    SearchComponent,
    FoldersComponent,
    MapPageComponent,
    CalendarComponent
} from './components/pages/user-page';

import { HeaderComponent } from './components/shared/header/header.component';
import { LightboxComponent } from './components/shared/lightbox/lightbox.component';


import { routing } from './picili.routes';
import { AuthGuard } from './guards/auth.guard';
import { GuestGuard } from './guards/guest.guard';

import { UserPageResolve } from './resolves/userpage.resolve';
import { UserSettingsResolve } from './resolves/usersettings.resolve';


import { AuthService, HttpService, SearchService, HelperService } from './services';
import { GlobalVars } from './../env';

import { SideMenuComponent } from './components/shared/side-menu/side-menu.component';
import { LoginComponent } from './components/pages/login/login.component';
import { RegisterComponent } from './components/pages/register/register.component';
import { HomeComponent } from './components/pages/home/home.component';
import { UserPageComponent } from './components/pages/user-page/user-page.component';
import { SettingsComponent } from './components/pages/settings/settings.component';
import { UserPageLinkComponent } from './components/shared/header/user-page-link/user-page-link.component';
import { PeopleComponent } from './components/pages/user-page/people/people.component';
import { ElevationComponent } from './components/pages/user-page/elevation/elevation.component';
import { ColourComponent } from './components/pages/user-page/colour/colour.component';
import { ResultGridComponent } from './components/shared/result-grid/result-grid.component';
import { SortSelectComponent } from './components/shared/sort-select/sort-select.component';
import { MapComponent } from './components/shared/map/map.component';


import { CalendarPeriodPipe } from './components/pipes/calendar-period';

import { AgmCoreModule } from 'angular2-google-maps/core';

@NgModule({
  declarations: [
    PiciliAppComponent,
    SearchComponent,
    FoldersComponent,
    MapPageComponent,
    CalendarComponent,
    HeaderComponent,
    LightboxComponent,
    SideMenuComponent,
    LoginComponent,
    RegisterComponent,
    HomeComponent,
    UserPageComponent,
    SettingsComponent,
    UserPageLinkComponent,
    PeopleComponent,
    ElevationComponent,
    ColourComponent,
    ResultGridComponent,
    SortSelectComponent,
    MapComponent,
    CalendarPeriodPipe
  ],
  imports: [
    BrowserModule,
    FormsModule,
    HttpModule,
    routing,
    AgmCoreModule.forRoot({
      apiKey: 'AIzaSyDR4kOXozjam-Y3xaMxq9mSABoJxHzsXhM'
    })
  ],
  providers: [
    AuthService,
    HttpService,
    SearchService,
    HelperService,
    AuthGuard,
    GuestGuard,
    GlobalVars,
    UserPageResolve,
    UserSettingsResolve
    ],
  bootstrap: [PiciliAppComponent]
})
export class AppModule { }
