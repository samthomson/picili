import React from 'react'
import * as ReactRedux from 'react-redux'

import GQLTest from 'src/components/GQLTest'
import Login from 'src/components/Login'
import * as Actions from 'src/redux/actions'
import * as Selectors from 'src/redux/selectors'
import * as AuthUtil from 'src/util/auth'

const App: React.FunctionComponent = () => {
	const userIsAuthenticated = ReactRedux.useSelector(
		Selectors.userIsAuthenticated,
	)
	const token = AuthUtil.getToken()
	const dispatch = ReactRedux.useDispatch()

	const logOut = () => dispatch(Actions.logout())

	if (!userIsAuthenticated) {
		return <Login />
	}

	return (
		<div className="App">
			[WIP]
			<br />
			<GQLTest />
			<button onClick={logOut}>logout {token}</button>
		</div>
	)
}

export default App
