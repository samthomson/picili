import { ApolloServer, gql } from 'apollo-server'

import * as Mutations from './mutations'

const typeDefs = gql`
    input AuthInput {
        email: String!
        password: String!
    }
    type AuthResponse {
        token: String
        error: String
    }

    type Query {
        ping: String
    }
    type Mutation {
        login(authInput: AuthInput!): AuthResponse
    }
`

const resolvers = {
    Query: {
        ping: () => 'pinged ' + Math.random(),
    },
    Mutation: {
        login: Mutations.login,
    },
}

const server = new ApolloServer({
    typeDefs,
    resolvers,
    cors: {
        origin: '*', // <- allow request from all domains
        credentials: true,
    },
})
server.listen({ port: process.env.FRONTEND_API_PORT }).then(({ url }) => {
    // Logger.info(`Server ready at ${url}`)
    console.log(`server ready: url: ${url}, port: ${process.env.FRONTEND_API_PORT}`)
})
