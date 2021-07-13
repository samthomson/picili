// import { removeAuthToken, saveAuthToken } from 'src/lib/auth'
import * as AuthUtil from 'src/util/auth'
import { Action, ActionType } from 'src/redux/actions'
import { Store } from 'src/redux/store'

const initialState: Store = {
	userIsAuthenticated: false,
}

export function appReducers(
	state: Store = initialState,
	action: Action,
): Store {
	switch (action.type) {
		case ActionType.LOGOUT:
			AuthUtil.removeToken()
			return {
				...state,
				userIsAuthenticated: false,
			}
		case ActionType.LOGIN_SUCCEEDED:
			AuthUtil.saveToken(action.token)
			return {
				...state,
				userIsAuthenticated: true,
			}
		case ActionType.AUTH_STATUS_VERIFIED:
			const userIsAuthenticated = action.validToken
			return {
				...state,
				userIsAuthenticated,
			}

		default:
			return state
	}
}
