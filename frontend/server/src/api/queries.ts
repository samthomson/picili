import * as AuthUtil from '../util/auth'
import * as DBUtil from '../util/db'

type OverviewResponse = {
    unprocessedTasksCount: number
}

const overview = async (): Promise<OverviewResponse> => {
    return await DBUtil.overviewStats()
}

const queries = {
    ping: (parent, args, ctx) => {
        AuthUtil.verifyRequestIsAuthenticated(ctx)
        return `${ctx?.userId} pinged ${Math.random()}`
    },
    validateToken: (parent, args, ctx) => AuthUtil.requestHasValidCookieToken(ctx),
    overview,
}

export default queries
