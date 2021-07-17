import * as DBUtil from './util/db'
import * as AuthUtil from './util/auth'

type LoginResponse = {
    token?: string
    error?: string
}

const fakeToken = 'auth-token'

export const login = async (parent, args, context): Promise<LoginResponse> => {

    const user = await DBUtil.getUser(args.authInput.email, args.authInput.password)
    const token = AuthUtil.generateJWT()

    context.setCookies.push({
        name: "picili-token",
        value: token,
        options: {
            SameSite: 'Strict',
            maxAge: 1000 * 60 * 60 * 24 * 31
        }
    })

    return {
        token: user ? token : undefined,
        error: !user ? `credentials didn't match a user` : undefined,
    }
}

export const register = (parent, args): LoginResponse => {
    return {
        token: args?.authInput.email ? fakeToken : undefined,
        error: !args?.authInput.email ? `no email provided` : undefined,
    }
}
