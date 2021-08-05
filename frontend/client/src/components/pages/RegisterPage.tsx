import * as React from 'react'
import { NavLink } from 'react-router-dom'

import Register from 'src/components/Register'
import PageTemplate from 'src/components/structure/PageTemplate'

const RegisterPage: React.FunctionComponent = () => {
	return (
		<PageTemplate>
			<div>
				<Register />
				<NavLink exact={true} className="item" to="/login">
					Login
				</NavLink>
			</div>
		</PageTemplate>
	)
}

export default RegisterPage
