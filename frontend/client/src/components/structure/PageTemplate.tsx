import * as React from 'react'

import Header from 'src/components/partials/Header'

interface Props {
	children: React.ReactNode
}

const PageTemplate: React.FunctionComponent<Props> = ({ children }: Props) => {
	return (
		<div>
			<Header />
			{children}
		</div>
	)
}

export default PageTemplate
