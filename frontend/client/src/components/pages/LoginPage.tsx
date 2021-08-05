import * as React from 'react'

import Login from 'src/components/Login'
import PageTemplate from 'src/components/structure/PageTemplate'

const LoginPage: React.FunctionComponent = () => {
	return (
		<PageTemplate>
			<div>
				<Login />
			</div>
		</PageTemplate>
	)
}

export default LoginPage
