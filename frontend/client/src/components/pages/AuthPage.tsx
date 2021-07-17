import * as React from 'react'

import Login from 'src/components/Login'
import PageTemplate from 'src/components/pages/PageTemplate'

const AuthPage: React.FunctionComponent = () => {
	return (
		<PageTemplate>
			<div>
				<h2>Auth</h2>

				<Login />
			</div>
		</PageTemplate>
	)
}

export default AuthPage
