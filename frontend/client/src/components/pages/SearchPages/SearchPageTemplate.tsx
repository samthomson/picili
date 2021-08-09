import * as React from 'react'
import { NavLink } from 'react-router-dom'

import PageTemplate from 'src/components/structure/PageTemplate'

interface Props {
	children: React.ReactNode
}

const SearchPageTemplate: React.FunctionComponent<Props> = ({
	children,
}: Props) => {
	return (
		<PageTemplate>
			<div id="menu-search-container">
				<div id="side-menu">
					<ul>
						<li>
							<NavLink exact={true} className="item" to="/">
								<span>Home</span>
							</NavLink>
						</li>
						<li>
							<NavLink exact={true} className="item" to="/search">
								<span>Search</span>
							</NavLink>
						</li>
						<li>
							<NavLink
								exact={true}
								className="item"
								to="/folders"
							>
								<span>Folders</span>
							</NavLink>
						</li>
						<li>
							<NavLink exact={true} className="item" to="/map">
								<span>Map</span>
							</NavLink>
						</li>
						<li>
							<NavLink
								exact={true}
								className="item"
								to="/calendar"
							>
								<span>Calendar</span>
							</NavLink>
						</li>
					</ul>
				</div>

				<div id="results-space">
					<div id="search-bar-space" className="ui vertical segment">
						[search bar]
					</div>
					<div id="filters-block" className="ui vertical segment">
						[filters block]
					</div>
					<div>[mode specific results summary]</div>
					<div>[mode specific controls; calendar, elevation]</div>

					<div id="page-space">
						page space
						<br />
						{children}
					</div>
				</div>
			</div>
		</PageTemplate>
	)
}

export default SearchPageTemplate
