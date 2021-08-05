import * as React from 'react'

import PageTemplate from 'src/components/structure/PageTemplate'
import GQLTest from 'src/components/GQLTest'

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
