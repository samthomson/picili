import * as React from 'react'
import { NavLink } from 'react-router-dom'

import Login from 'src/components/Login'
import PageTemplate from 'src/components/structure/PageTemplate'

const LoginPage: React.FunctionComponent = () => {
	return (
		<PageTemplate>
			<div>
				<h2>Login</h2>

				<Login />
				<NavLink exact={true} className="item" to="/register">
					Register
				</NavLink>
			</div>
		</PageTemplate>
	)
}

export default LoginPage
