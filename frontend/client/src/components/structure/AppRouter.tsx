import React from 'react'
import * as ReactRedux from 'react-redux'
import { BrowserRouter as Router } from 'react-router-dom'

import * as Selectors from 'src/redux/selectors'

import { GuestOnlyRoute } from 'src/components/structure/GuestOnlyRoute'
import {
	IProtectedRouteProps,
	ProtectedRoute,
} from 'src/components/structure/ProtectedRoute'
import LoginPage from 'src/components/pages/LoginPage'
import RegisterPage from 'src/components/pages/RegisterPage'
import HomePage from 'src/components/pages/HomePage'
import AdminPage from 'src/components/pages/AdminPage'

const AppRouter: React.FunctionComponent = () => {
	const isAuthenticated = ReactRedux.useSelector(
		Selectors.userIsAuthenticated,
	)

	const defaultGuestRouteProps: IProtectedRouteProps = {
		isAuthenticated,
	}

	const defaultProtectedRouteProps: IProtectedRouteProps = {
		isAuthenticated,
	}

	return (
		<Router>
			<ProtectedRoute
				{...defaultProtectedRouteProps}
				key={'home'}
				path={'/'}
				exact={true}
				component={HomePage}
			/>

			<ProtectedRoute
				{...defaultProtectedRouteProps}
				key={'admin'}
				path={'/admin'}
				exact={false}
				component={AdminPage}
			/>

			<GuestOnlyRoute
				{...defaultGuestRouteProps}
				key={'login'}
				path={'/login'}
				exact={false}
				component={LoginPage}
			/>

			<GuestOnlyRoute
				{...defaultGuestRouteProps}
				key={'register'}
				path={'/register'}
				exact={false}
				component={RegisterPage}
			/>
		</Router>
	)
}

export default AppRouter
