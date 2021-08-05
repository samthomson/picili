import * as React from 'react'
import { useQuery, gql } from '@apollo/client'

import AdminTemplate from 'src/components/structure/AdminTemplate'

const overviewQuery = gql`
	query overview {
		overview {
			unprocessedTasksCount
		}
	}
`

const AdminOverview: React.FunctionComponent = () => {
	const { loading, error, data } = useQuery(overviewQuery)

	if (loading) {
		return <>loading...</>
	}

	if (error) {
		return <>{error?.message}</>
	}

	return (
		<AdminTemplate>
			<div>{data?.overview.unprocessedTasksCount} tasks</div>
		</AdminTemplate>
	)
}

export default AdminOverview
