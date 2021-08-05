import * as React from 'react'
import * as ReactRedux from 'react-redux'
import { NavLink } from 'react-router-dom'

import * as Actions from 'src/redux/actions'
import * as Selectors from 'src/redux/selectors'

interface Props {
	children: React.ReactNode
}

const PageTemplate: React.FunctionComponent<Props> = ({ children }: Props) => {
	const isAuthenticated = ReactRedux.useSelector(
		Selectors.userIsAuthenticated,
	)

	const dispatch = ReactRedux.useDispatch()
	const logOut = () => dispatch(Actions.logout())

	return (
		<div>
			<div>
				{isAuthenticated && (
					<>
						<ul>
							<li>
								<NavLink exact={true} className="item" to="/">
									Home
								</NavLink>
							</li>
							<li>
								<NavLink
									exact={true}
									className="item"
									to="/admin"
								>
									Admin
								</NavLink>
							</li>
						</ul>
						{isAuthenticated && <>[authed]</>}
						<button onClick={logOut}>logout</button>
						<hr />
					</>
				)}
			</div>
			{children}
		</div>
	)
}

export default PageTemplate
