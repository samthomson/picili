import * as React from 'react'
import * as ReactRedux from 'react-redux'
import { useMutation, gql } from '@apollo/client'

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

	return (
		<React.Fragment>
			<h2>register</h2>
			{loading && 'loading..'}
			<br />
			{httpError?.message}
			{data?.register.error && <b>{data.register.error}</b>}
			<form onSubmit={registerHandler}>
				{error && <b>{error}</b>}
				<input
					type="text"
					value={email}
					onChange={(e) => setEmail(e.target.value)}
					disabled={loading}
					placeholder="email"
				/>
				<br />

				<input
					type="password"
					value={password}
					onChange={(e) => setPassword(e.target.value)}
					disabled={loading}
					placeholder="password"
				/>
				<br />

				<input
					type="password"
					value={passwordConfirmation}
					onChange={(e) => setPasswordConfirmation(e.target.value)}
					disabled={loading}
					placeholder="repeat password"
				/>
				<br />

				<button type="submit" disabled={loading}>
					register
				</button>
			</form>
			<hr />
		</React.Fragment>
	)
}

export default Register
