import * as React from 'react'

import Login from 'src/components/Login'
import Register from 'src/components/Register'
import PageTemplate from 'src/components/pages/PageTemplate'

const AuthPage: React.FunctionComponent = () => {
	return (
		<PageTemplate>
			<div>
				<h2>Login or Register</h2>

				<Login />
				<Register />
			</div>
		</PageTemplate>
	)
}

export default AuthPage
