import * as DBUtil from './util/db'

type LoginResponse = {
    token?: string
    error?: string
}

const fakeToken = 'auth-token'

export const login = async (parent, args): Promise<LoginResponse> => {

    const user = await DBUtil.getUser(args.authInput.email, args.authInput.password)
    console.log('user exists', !!user)

    return {
        token: user ? fakeToken + Math.random() : undefined,
        error: !user ? `credentials didn't match a user` : undefined,
    }
}

export const register = (parent, args): LoginResponse => {
    return {
        token: args?.authInput.email ? fakeToken : undefined,
        error: !args?.authInput.email ? `no email provided` : undefined,
    }
}
