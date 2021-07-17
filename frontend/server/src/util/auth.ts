import * as jwt from 'jsonwebtoken'

export const generateJWT = (): string => {
	return jwt.sign(
		{ 'picili-user': true },
		process.env.JWT_COOKIE_SECRET || 'MISSING_SECRET',
		{
			expiresIn: '30 days',
		},
	)
}

export const isJWTValid = (jwtToken: string): boolean => {
	try {
		jwt.verify(jwtToken, process.env.JWT_COOKIE_SECRET || 'MISSING_SECRET')
		return true
	} catch (error) {
		return false
	}
}


export const requestHasValidAuthenticationCookie = (
	req,
): boolean => {
	const authCookie = req?.cookies?.['picili-token']
    
    const tokenIsFromPicili = isJWTValid(authCookie)

	return tokenIsFromPicili
}

export const verifyRequestIsAuthenticated = (ctx): boolean => {
	if (ctx?.isAuthenticated) {
		return true
	}
	throw new Error('401')
}

