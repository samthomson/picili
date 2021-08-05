import * as React from 'react'
import { NavLink } from 'react-router-dom'

import PageTemplate from 'src/components/structure/PageTemplate'

interface Props {
	children: React.ReactNode
}

const AdminTemplate: React.FunctionComponent<Props> = ({ children }: Props) => {
	return (
		<PageTemplate>
			<div>
				<h2>Admin</h2>
				<hr />
				<ul>
					<li>
						<NavLink exact={true} className="item" to="/admin">
							Queues
						</NavLink>
					</li>
					<li>
						<NavLink exact={true} className="item" to="/admin/keys">
							Keys
						</NavLink>
					</li>
					<li>
						<NavLink
							exact={true}
							className="item"
							to="/admin/dropbox"
						>
							Dropbox
						</NavLink>
					</li>
				</ul>
				<hr />
				{children}
			</div>
		</PageTemplate>
	)
}

export default AdminTemplate
