import * as React from 'react'
import { Redirect, Route, RouteProps } from 'react-router'

export interface IProtectedRouteProps extends RouteProps {
	isAuthenticated: boolean
}

export class ProtectedRoute extends Route<IProtectedRouteProps> {
	public render(): React.ReactElement {
		let redirectPath = ''
		if (!this.props.isAuthenticated) {
			redirectPath = '/login'
		}

		if (redirectPath) {
			const renderComponent = () => (
				<Redirect to={{ pathname: redirectPath }} />
			)
			return (
				<Route
					{...this.props}
					component={renderComponent}
					render={undefined}
				/>
			)
		} else {
			return <Route {...this.props} />
		}
	}
}
