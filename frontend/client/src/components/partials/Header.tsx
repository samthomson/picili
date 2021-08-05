import * as React from 'react'
import * as ReactRedux from 'react-redux'
import { NavLink } from 'react-router-dom'

import * as Actions from 'src/redux/actions'
import * as Selectors from 'src/redux/selectors'

const Header: React.FunctionComponent = () => {
	const loadingSomething = false // searchService.bSearching || httpService.bMakingRequestToServer
	const isAuthenticated = ReactRedux.useSelector(
		Selectors.userIsAuthenticated,
	)

	const dispatch = ReactRedux.useDispatch()
	const logOut = () => dispatch(Actions.logout())

	return (
		<React.Fragment>
			{loadingSomething && (
				<div className="indeterminate-loading-bar progress">
					<div className="indeterminate"></div>
				</div>
			)}

			<div id="header">
				<span className="header-font">
					<NavLink exact={true} className="header-font" to="/">
						picili
					</NavLink>
				</span>

				<div id="top-right-links">
					{isAuthenticated && (
						<div>
							{/* <span
								id="processing-header-output"
								*ngIf="bProcessing"
								class="ui basic tiny label"
								title="{{cProcessingFiles | number}} file(s) to go, across {{cProcessingTasks | number}} task(s)"
							>
								<i class="fa fa-cogs"></i>&nbsp;
								Synchronising / Processing
							</span> */}

							<NavLink
								exact={true}
								className="ui tiny button"
								to="/admin"
							>
								<i className="fa fa-cog" aria-hidden="true"></i>
								&nbsp;settings
							</NavLink>

							<button className="ui tiny button" onClick={logOut}>
								<i className="fa fa-cog" aria-hidden="true"></i>
								&nbsp;logout
							</button>
						</div>
					)}
					{!isAuthenticated && (
						<div>
							<NavLink
								exact={true}
								className="ui tiny button"
								to="/login"
							>
								login
							</NavLink>

							<NavLink
								exact={true}
								className="ui tiny button"
								to="/register"
							>
								register
							</NavLink>
						</div>
					)}
				</div>
			</div>
		</React.Fragment>
	)
}

export default Header
