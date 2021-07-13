const tokenName = 'picili-token'

export const getToken = (): string | undefined => {
	return localStorage.getItem(tokenName) ?? undefined
}

export const saveToken = (token: string): void => {
	localStorage.setItem(tokenName, token)
}

export const removeToken = (): void => {
	localStorage.removeItem(tokenName)
}
