import * as AuthUtil from '../util/auth'

const queries = {
    ping: (parent, args, ctx) => {
        AuthUtil.verifyRequestIsAuthenticated(ctx)
        return `${ctx?.userId} pinged ${Math.random()}`
    },
    validateToken: (parent, args, { req }) => AuthUtil.requestHasValidAuthenticationCookie(req),
}

export default queries
