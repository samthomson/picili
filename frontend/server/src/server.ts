import { ApolloServer, gql } from 'apollo-server-express'
import express from 'express'
import httpHeadersPlugin from 'apollo-server-plugin-http-headers'
import { ApolloServerPluginLandingPageGraphQLPlayground } from 'apollo-server-core'
import cookieParser from 'cookie-parser'
import cors from 'cors'

import * as Mutations from './mutations'
import * as AuthUtil from './util/auth'

const startServer = async () => {
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

        type Query {
            ping: String
            validateToken(token: String!): Boolean
        }
        type Mutation {
            login(authInput: LoginInput!): AuthResponse
            register(authInput: RegisterInput!): AuthResponse
        }
    `

    const resolvers = {
        Query: {
            ping: (parent, args, ctx) => {
                AuthUtil.verifyRequestIsAuthenticated(ctx)
                return `${ctx?.userId} pinged ${Math.random()}`
            },
            validateToken: (parent, args, { req }) => AuthUtil.requestHasValidAuthenticationCookie(req),
        },
        Mutation: {
            login: Mutations.login,
            register: Mutations.register,
        },
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

    await server.start()

    const app = express()
    app.use(cookieParser())

    const corsOptions = {
        origin: true, // anyone can connect, simpler - for now - than specifying client host which will change with deployment
        credentials: true, // <-- REQUIRED backend setting
    }
    app.use(cors(corsOptions))

    server.applyMiddleware({
        app,
        cors: false, // very important so that express cors middleware settings are used
    })

    await new Promise((resolve) => app.listen({ port: process.env.FRONTEND_API_PORT }, resolve))

    return { server, app }
}

startServer()
