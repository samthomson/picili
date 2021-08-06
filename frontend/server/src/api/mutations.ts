import * as DBUtil from '../util/db'
import * as AuthUtil from '../util/auth'
import * as Types from '../declarations'

const fakeToken = 'auth-token'

const login = async (parent, args, context): Promise<Types.API.Response.Auth> => {
    const user = await DBUtil.getUser(args.authInput.email, args.authInput.password)

    if (user) {
        const token = AuthUtil.generateJWT(user.id)

        context.setCookies.push({
            name: 'picili-token',
            value: token,
            options: {
                SameSite: 'Strict',
                maxAge: 1000 * 60 * 60 * 24 * 31,
            },
        })

        return {
            token,
            error: undefined,
        }
    } else {
        return {
            token: undefined,
            error: `credentials didn't match a user`,
        }
    }
}

const register = async (parent, args, context): Promise<Types.API.Response.Auth> => {
    const { email, password, passwordConfirmation } = args.authInput

    // check email not in use
    const userWithEmailExists = await DBUtil.userWithEmailExists(email)
    if (userWithEmailExists) {
        return {
            error: 'User with email exists',
        }
    }

    // check passwords match
    if (password !== passwordConfirmation) {
        return {
            error: "Passwords don't match",
        }
    }

    // create user
    const user = await DBUtil.createUser(email, password)

    // authenticate user

    const token = AuthUtil.generateJWT(user.id)

    context.setCookies.push({
        name: 'picili-token',
        value: token,
        options: {
            SameSite: 'Strict',
            maxAge: 1000 * 60 * 60 * 24 * 31,
        },
    })

    // return token or error
    return {
        token: user ? token : undefined,
        error: !user ? `user creation failed` : undefined,
    }
}

const mutations = {
    login,
    register,
}

export default mutations
