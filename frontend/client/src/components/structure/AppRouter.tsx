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
import AdminQueues from 'src/components/pages/AdminQueues'
import AdminKeys from 'src/components/pages/AdminKeys'
import AdminDropbox from 'src/components/pages/AdminDropbox'

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
			<ProtectedRoute
				{...defaultProtectedRouteProps}
				key={'home'}
				path={'/'}
				exact={true}
				component={HomePage}
			/>

			<ProtectedRoute
				{...defaultProtectedRouteProps}
				path="/admin"
				render={({ match: { url } }) => (
					<>
						<ProtectedRoute
							{...defaultProtectedRouteProps}
							path={`${url}/`}
							component={AdminQueues}
							exact
						/>
						<ProtectedRoute
							{...defaultProtectedRouteProps}
							path={`${url}/keys`}
							component={AdminKeys}
						/>
						<ProtectedRoute
							{...defaultProtectedRouteProps}
							path={`${url}/dropbox`}
							component={AdminDropbox}
						/>
					</>
				)}
			/>
		</Router>
	)
}

export default AppRouter
