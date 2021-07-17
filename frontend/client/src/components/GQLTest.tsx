import * as React from 'react'
import { useQuery, gql } from '@apollo/client'

const pingQuery = gql`
	query ping {
		ping
	}
`

const GQLTest: React.FunctionComponent = () => {
	const { loading, error, data, refetch } = useQuery(pingQuery)

	if (loading) {
		return <>loading...</>
	}
	if (error) {
		console.log(error)
	}

	return (
		<React.Fragment>
			api says: {data?.ping}
			<a href="#" onClick={() => refetch()}>
				refetch
			</a>
			{error && <p>error calling api... {error.message}</p>}
		</React.Fragment>
	)
}

export default GQLTest
