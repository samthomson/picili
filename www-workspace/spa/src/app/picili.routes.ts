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
import { HomeResolve } from './resolves/home.resolve';


const routes = [
    {
        path: '',
        redirectTo: '/login',
        pathMatch: 'full'
    },
    {
        path: 'login',
        component: LoginComponent,
        canActivate: [GuestGuard]
    },
    {
        path: 'register',
        component: RegisterComponent
    },
    {
        path: ':username/settings',
        component: SettingsComponent,
        resolve: { userSettings: UserSettingsResolve},
        canActivate: [AuthGuard]
    },
    {
        path: ':username/:searchmode',
        name: 'user-search-specific',
        component: UserPageComponent,
        canActivate: [AuthGuard]
    },
    {
        path: ':username',
        name: 'user-search-default',
        component: UserPageComponent,
        resolve: { homeAggs: HomeResolve},
        canActivate: [AuthGuard]
    }
];

export const routing = RouterModule.forRoot(routes);
