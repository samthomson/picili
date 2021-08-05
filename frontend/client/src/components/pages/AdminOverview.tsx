import * as React from 'react'

import AdminTemplate from 'src/components/structure/AdminTemplate'

const AdminOverview: React.FunctionComponent = () => {
	return (
		<AdminTemplate>
			<div>
				[overview stats; total files, dropbox connected or not, total
				tags, % files with geo data, no. tasks queued]
			</div>
		</AdminTemplate>
	)
}

export default AdminOverview
