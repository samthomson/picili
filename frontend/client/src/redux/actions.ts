export enum ActionType {
	LOGIN_ATTEMPT = 'LOGIN_ATTEMPT',
	LOGIN_SUCCEEDED = 'LOGIN_SUCCEEDED',
	LOGIN_FAILED = 'LOGIN_FAILED',
	LOGOUT = 'LOGOUT',
	AUTH_STATUS_VERIFIED = 'AUTH_STATUS_VERIFIED',
	AUTH_STATUS_VERIFY = 'AUTH_STATUS_VERIFY',
}

export type Action =
	| {
			type: ActionType.LOGOUT
	  }
	| {
			type: ActionType.LOGIN_SUCCEEDED
			token: string
	  }
	| {
			type: ActionType.LOGIN_FAILED
			token: string
	  }
	| {
			type: ActionType.AUTH_STATUS_VERIFY
	  }
	| {
			type: ActionType.AUTH_STATUS_VERIFIED
			validToken: boolean
	  }

export type LoginAction = {
	type: ActionType.LOGIN_ATTEMPT
	email: string
}

export const attemptLogin = (email: string): LoginAction => {
	return {
		type: ActionType.LOGIN_ATTEMPT,
		email,
	}
}

export const attemptLoginSucceeded = (token: string): Action => {
	return {
		type: ActionType.LOGIN_SUCCEEDED,
		token,
	}
}

export const attemptLoginFailed = (): Action => {
	return {
		type: ActionType.LOGIN_FAILED,
		token: '',
	}
}

export const logout = (): Action => {
	return {
		type: ActionType.LOGOUT,
	}
}

export const verifyAuthStatus = (): Action => {
	return {
		type: ActionType.AUTH_STATUS_VERIFY,
	}
}

export const verifiedAuthStatus = (validToken: boolean): Action => {
	return {
		type: ActionType.AUTH_STATUS_VERIFIED,
		validToken,
	}
}
