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
		return <>error calling api... {error?.message}</>
	}
	console.log(data)
	const { ping } = data

	return (
		<React.Fragment>
			api says: {ping}
			<a href="#" onClick={() => refetch()}>
				refetch
			</a>
		</React.Fragment>
	)
}

export default GQLTest
