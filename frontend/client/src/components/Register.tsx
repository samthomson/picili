import * as React from 'react'
import * as ReactRedux from 'react-redux'
import { useMutation, gql } from '@apollo/client'
import { NavLink } from 'react-router-dom'
import classNames from 'classnames'
import * as Actions from 'src/redux/actions'

const registerQuery = gql`
	mutation register($authInput: RegisterInput!) {
		register(authInput: $authInput) {
			token
			error
		}
	}
`

const Register: React.FunctionComponent = () => {
	const [email, setEmail] = React.useState<string>('')
	const [password, setPassword] = React.useState<string>('')
	const [passwordConfirmation, setPasswordConfirmation] =
		React.useState<string>('')
	const [error, setError] = React.useState<string>('')

	const [registerMutation, { error: httpError, data, loading = false }] =
		useMutation(registerQuery)

	const dispatch = ReactRedux.useDispatch()

	React.useEffect(() => {
		if (data?.register.token) {
			dispatch(Actions.attemptLoginSucceeded(data.register.token))
		}
		if (data?.register.error) {
			setError(data.register.error)
		}
	}, [data])

	const registerHandler = async (e: React.FormEvent) => {
		e.preventDefault()
		await registerMutation({
			variables: { authInput: { email, password, passwordConfirmation } },
		})
	}

	const registrationFailed =
		httpError?.message || data?.register.error || error
	const formDisabled =
		loading ||
		!(email !== '' && password !== '' && passwordConfirmation !== '')

	return (
		<React.Fragment>
			<div className="ui middle aligned center aligned grid">
				<div className="middle-form-column">
					<h2 className="ui header">
						<div className="content">Register</div>
					</h2>

					<form onSubmit={registerHandler} className="ui large form">
						{registrationFailed && (
							<div className="ui red segment">
								<strong>{registrationFailed}</strong>
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
									error: registrationFailed,
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
									error: registrationFailed,
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

							<div
								className={classNames({
									field: true,
									error: registrationFailed,
								})}
							>
								<div className="ui left icon input">
									<i className="lock icon"></i>

									<input
										type="password"
										value={passwordConfirmation}
										onChange={(e) =>
											setPasswordConfirmation(
												e.target.value,
											)
										}
										disabled={loading}
										placeholder="confirm password"
									/>
								</div>
							</div>

							<button
								type="submit"
								disabled={formDisabled}
								className="ui fluid large button primary submit"
							>
								Register
							</button>
						</div>
					</form>

					<div>
						or{' '}
						<NavLink
							exact={true}
							className="picili-link"
							to="/login"
						>
							login
						</NavLink>
					</div>
				</div>
			</div>
		</React.Fragment>
	)
}

export default Register
