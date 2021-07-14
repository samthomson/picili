import * as React from 'react'
import * as ReactRedux from 'react-redux'
import { useMutation, gql } from '@apollo/client'

import * as Actions from 'src/redux/actions'

const loginQuery = gql`
	mutation login($authInput: AuthInput!) {
		login(authInput: $authInput) {
			token
			error
		}
	}
`

const Login: React.FunctionComponent = () => {
	const [email, setEmail] = React.useState<string>('')
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

	const loginHandler = async (e: React.FormEvent) => {
		e.preventDefault()
		await loginMutation({ variables: { authInput: { email } } })
	}

	return (
		<React.Fragment>
			<h2>login</h2>
			loading: _{String(loading)}_
			<br />
			{httpError?.message}
			{data?.login.error && <b>{data.login.error}</b>}
			<form onSubmit={loginHandler}>
				{error && <b>{error}</b>}
				<input
					type="text"
					value={email}
					onChange={(e) => setEmail(e.target.value)}
					disabled={loading}
				/>

				<button type="submit" disabled={loading}>
					login
				</button>
			</form>
			<hr />
		</React.Fragment>
	)
}

export default Login