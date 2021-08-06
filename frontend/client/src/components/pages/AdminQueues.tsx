import * as React from 'react'
import { useQuery, gql } from '@apollo/client'

import AdminTemplate from 'src/components/structure/AdminTemplate'

const queueSummaryQuery = gql`
	query queueSummary {
		queues {
			unprocessedTasksCount
			queueSummaries {
				processor
				taskCount
				oldest
			}
		}
	}
`

const AdminQueues: React.FunctionComponent = () => {
	const { loading, error, data } = useQuery(queueSummaryQuery)

	if (loading) {
		return <>loading...</>
	}

	if (error) {
		return <>{error?.message}</>
	}

	const { unprocessedTasksCount, queueSummaries } = data.queues

	return (
		<AdminTemplate>
			<div>{unprocessedTasksCount} tasks</div>
			<table className="ui celled table">
				<thead>
					<tr>
						<th>Processor</th>
						<th># tasks</th>
						<th>ready since</th>
					</tr>
				</thead>
				<tbody>
					{queueSummaries.map(
						(
							queue: {
								processor: string
								taskCount: number
								oldest: string
							},
							index: number,
						) => {
							return (
								<tr key={index}>
									<td>{queue.processor}</td>
									<td>{queue.taskCount}</td>
									<td>{queue.oldest}</td>
								</tr>
							)
						},
					)}
				</tbody>
			</table>
		</AdminTemplate>
	)
}

export default AdminQueues
