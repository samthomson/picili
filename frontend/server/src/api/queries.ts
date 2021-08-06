import * as AuthUtil from '../util/auth'
import * as DBUtil from '../util/db'
import * as Types from '../declarations'

const overview = async (): Promise<Types.API.Response.Overview> => {
    return await DBUtil.overviewStats()
}

const queues = async (): Promise<Types.API.Response.Queue> => {
    return await DBUtil.queueSummaries()
}

const queries = {
    ping: (parent, args, ctx) => {
        AuthUtil.verifyRequestIsAuthenticated(ctx)
        return `${ctx?.userId} pinged ${Math.random()}`
    },
    validateToken: (parent, args, ctx) => AuthUtil.requestHasValidCookieToken(ctx),
    overview,
    queues,
}

export default queries
