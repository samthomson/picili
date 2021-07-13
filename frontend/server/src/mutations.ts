type LoginResponse = {
    token?: string
    error?: string
}

const fakeToken = 'auth-token'

export const login = (parent, args): LoginResponse => {
    return {
        token: args?.authInput.email ? fakeToken + Math.random() : undefined,
        error: !args?.authInput.email ? `no email provided` : undefined,
    }
}

export const register = (parent, args): LoginResponse => {
    return {
        token: args?.authInput.email ? fakeToken : undefined,
        error: !args?.authInput.email ? `no email provided` : undefined,
    }
}
