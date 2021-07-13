import { Store } from 'src/redux/store'

export const userIsAuthenticated = (state: Store): boolean => {
	return state.userIsAuthenticated
}
