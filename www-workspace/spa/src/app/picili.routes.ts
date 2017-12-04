import { RouterModule } from "@angular/router";
import { AuthGuard } from './guards/auth.guard';
import { GuestGuard } from './guards/guest.guard';

import {
    HomeComponent,
    UserPageComponent,
    LoginComponent,
    RegisterComponent,
    SettingsComponent
} from './components/pages';

import { UserPageResolve } from './resolves/userpage.resolve';
import { UserSettingsResolve } from './resolves/usersettings.resolve';



const routes = [

    { path: '', component: HomeComponent},

    { path: 'login', component: LoginComponent},
    { path: 'register', component: RegisterComponent},

    { path: ':username/settings', component: SettingsComponent, resolve: { userSettings: UserSettingsResolve}, canActivate: [AuthGuard] },
    
    { path: ':username/:searchmode', name: 'user-search-specific', component: UserPageComponent}/*

    { path: ':username', name: 'user-search-default', component: UserPageComponent}*/
];

export const routing = RouterModule.forRoot(routes);
