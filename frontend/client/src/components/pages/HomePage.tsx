import * as React from 'react'

import PageTemplate from 'src/components/pages/PageTemplate'
import GQLTest from 'src/componenytrts/GQLTest'

const HomePage: React.FunctionComponent = () => {
	return (
		<PageTemplate>
			<div>
				<h2>Home</h2>
				<GQLTest />
			</div>
		</PageTemplate>
	)
}

export default HomePage
