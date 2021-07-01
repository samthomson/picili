import { ApolloServer, gql } from 'apollo-server'

const typeDefs = gql`
    type Query {
        ping: String
    }
`

const resolvers = {
    Query: {
        ping: () => 'pinged ' + Math.random(),
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
