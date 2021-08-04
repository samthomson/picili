import * as React from 'react'
import { NavLink } from 'react-router-dom'

import Register from 'src/components/Register'
import PageTemplate from 'src/components/pages/PageTemplate'

const RegisterPage: React.FunctionComponent = () => {
	return (
		<PageTemplate>
			<div>
				<h2>Register</h2>

				<Register />
				<NavLink exact={true} className="item" to="/login">
					Login
				</NavLink>
			</div>
		</PageTemplate>
	)
}

export default RegisterPage
