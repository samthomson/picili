import * as React from 'react'

import Register from 'src/components/Register'
import PageTemplate from 'src/components/structure/PageTemplate'

const RegisterPage: React.FunctionComponent = () => {
	return (
		<PageTemplate>
			<div>
				<Register />
			</div>
		</PageTemplate>
	)
}

export default RegisterPage
