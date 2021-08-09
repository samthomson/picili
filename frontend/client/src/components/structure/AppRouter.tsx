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
import DefaultSearch from 'src/components/pages/SearchPages/DefaultSearch'
import FolderSearch from 'src/components/pages/SearchPages/FolderSearch'
import MapSearch from 'src/components/pages/SearchPages/MapSearch'
import CalendarSearch from 'src/components/pages/SearchPages/CalendarSearch'

import AdminOverview from 'src/components/pages/AdminOverview'
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
				key={'search'}
				path={'/search'}
				exact={true}
				component={DefaultSearch}
			/>
			<ProtectedRoute
				{...defaultProtectedRouteProps}
				key={'folders'}
				path={'/folders'}
				exact={true}
				component={FolderSearch}
			/>
			<ProtectedRoute
				{...defaultProtectedRouteProps}
				key={'map'}
				path={'/map'}
				exact={true}
				component={MapSearch}
			/>
			<ProtectedRoute
				{...defaultProtectedRouteProps}
				key={'calendar'}
				path={'/calendar'}
				exact={true}
				component={CalendarSearch}
			/>

			<ProtectedRoute
				{...defaultProtectedRouteProps}
				path="/admin"
				render={({ match: { url } }) => (
					<>
						<ProtectedRoute
							{...defaultProtectedRouteProps}
							path={`${url}/`}
							component={AdminOverview}
							exact
						/>
						<ProtectedRoute
							{...defaultProtectedRouteProps}
							path={`${url}/queues`}
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
