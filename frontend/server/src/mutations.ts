import * as DBUtil from './util/db'
import * as AuthUtil from './util/auth'

type AuthResponse = {
    token?: string
    error?: string
}

const fakeToken = 'auth-token'

export const login = async (parent, args, context): Promise<AuthResponse> => {

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

export const register = async (parent, args, context): Promise<AuthResponse> => {

    const { email, password, passwordConfirmation } = args.authInput

    // check email not in use
    const userWithEmailExists = await DBUtil.userWithEmailExists(email)
    if (userWithEmailExists) {
        return {
            error: "User with email exists"
        }
    }

    // check passwords match
    if (password !== passwordConfirmation) {
        return {
            error: "Passwords don't match"
        }
    }

    // create user
    const user = await DBUtil.createUser(email, password)

    // authenticate user

    const token = AuthUtil.generateJWT()

    console.log('id: ', user.id)

    context.setCookies.push({
        name: "picili-token",
        value: token,
        options: {
            SameSite: 'Strict',
            maxAge: 1000 * 60 * 60 * 24 * 31
        }
    })

    // return token or error
    return {
        token: user ? token : undefined,
        error: !user ? `user creation failed` : undefined,
    }
}
