import { ApolloServer, gql } from 'apollo-server-express'
import httpHeadersPlugin from 'apollo-server-plugin-http-headers'
import { ApolloServerPluginLandingPageGraphQLPlayground } from 'apollo-server-core'

import Query from './queries'
import Mutation from './mutations'

import * as AuthUtil from '../util/auth'

const typeDefs = gql`
    input LoginInput {
        email: String!
        password: String!
    }
    input RegisterInput {
        email: String!
        password: String!
        passwordConfirmation: String!
    }
    type AuthResponse {
        token: String
        error: String
    }
    type OverviewResponse {
        unprocessedTasksCount: Int
    }

    type Query {
        ping: String
        validateToken(token: String!): Boolean
        overview: OverviewResponse
    }
    type Mutation {
        login(authInput: LoginInput!): AuthResponse
        register(authInput: RegisterInput!): AuthResponse
    }
`

const resolvers = {
    Query,
    Mutation,
}

const server = new ApolloServer({
    typeDefs,
    resolvers,
    plugins: [httpHeadersPlugin, ApolloServerPluginLandingPageGraphQLPlayground],
    context: (ctx) => {
        return {
            setCookies: [],
            setHeaders: [],
            userId: AuthUtil.userIdFromRequestCookie(ctx.req),
        }
    },
})

export default server
