import { Store } from 'src/redux/store'

export const userIsAuthenticated = (state: Store): boolean => {
	return state.userIsAuthenticated
}
export const somethingIsLoading = (state: Store): boolean => {
	return state.somethingIsLoading
}
