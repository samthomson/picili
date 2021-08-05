import * as React from 'react'
import { NavLink } from 'react-router-dom'

import PageTemplate from 'src/components/structure/PageTemplate'

interface Props {
	children: React.ReactNode
}

const AdminTemplate: React.FunctionComponent<Props> = ({ children }: Props) => {
	return (
		<PageTemplate>
			<div id="main-page">
				<div className="ui grid">
					<div className="four wide column">
						<div className="ui vertical fluid tabular menu">
							<NavLink exact={true} className="item" to="/admin">
								Overview
							</NavLink>

							<NavLink
								exact={true}
								className="item"
								to="/admin/queues"
							>
								Queues
							</NavLink>

							<NavLink
								exact={true}
								className="item"
								to="/admin/keys"
							>
								Keys
							</NavLink>

							<NavLink
								exact={true}
								className="item"
								to="/admin/dropbox"
							>
								Dropbox
							</NavLink>
						</div>
					</div>

					<div className="twelve wide stretched column">
						{children}
					</div>
				</div>
			</div>
		</PageTemplate>
	)
}

export default AdminTemplate
