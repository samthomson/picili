import * as React from 'react'
import * as ReactRedux from 'react-redux'
import { useMutation, gql } from '@apollo/client'
import classNames from 'classnames'
import { NavLink } from 'react-router-dom'

import * as Actions from 'src/redux/actions'

const loginQuery = gql`
	mutation login($authInput: LoginInput!) {
		login(authInput: $authInput) {
			token
			error
		}
	}
`

const Login: React.FunctionComponent = () => {
	const [email, setEmail] = React.useState<string>('')
	const [password, setPassword] = React.useState<string>('')
	const [error, setError] = React.useState<string>('')

	const [loginMutation, { error: httpError, data, loading = false }] =
		useMutation(loginQuery)

	const dispatch = ReactRedux.useDispatch()

	React.useEffect(() => {
		if (data?.login.token) {
			dispatch(Actions.attemptLoginSucceeded(data.login.token))
		}
		if (data?.login.error) {
			setError(data.login.error)
		}
	}, [data])

	React.useEffect(() => {
		dispatch(Actions.setGlobalLoadingState(loading))
	}, [loading])

	const loginHandler = async (e: React.FormEvent) => {
		e.preventDefault()
		await loginMutation({ variables: { authInput: { email, password } } })
	}

	const loginFailed = httpError?.message || data?.login.error || error
	const formDisabled = loading || !(email !== '' && password !== '')

	return (
		<React.Fragment>
			<div className="ui middle aligned center aligned grid">
				<div className="middle-form-column">
					<h2 className="ui header">
						<div className="content">Login</div>
					</h2>

					<form onSubmit={loginHandler} className="ui large form">
						{loginFailed && (
							<div className="ui red segment">
								<strong>{loginFailed}</strong>
							</div>
						)}

						<div
							className={classNames({
								'ui stacked segment': true,
								loading: loading,
							})}
						>
							<div
								className={classNames({
									field: true,
									error: loginFailed,
								})}
							>
								<div className="ui left icon input">
									<i className="user icon"></i>

									<input
										type="text"
										value={email}
										onChange={(e) =>
											setEmail(e.target.value)
										}
										disabled={loading}
										placeholder="email"
									/>
								</div>
							</div>

							<div
								className={classNames({
									field: true,
									error: loginFailed,
								})}
							>
								<div className="ui left icon input">
									<i className="lock icon"></i>

									<input
										type="password"
										value={password}
										onChange={(e) =>
											setPassword(e.target.value)
										}
										disabled={loading}
										placeholder="password"
									/>
								</div>
							</div>

							<button
								type="submit"
								disabled={formDisabled}
								className="ui fluid large button primary submit"
							>
								Login
							</button>
						</div>
					</form>

					<div>
						or{' '}
						<NavLink
							exact={true}
							className="picili-link"
							to="/register"
						>
							register
						</NavLink>
					</div>
				</div>
			</div>
		</React.Fragment>
	)
}

export default Login
