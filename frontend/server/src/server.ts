import express from 'express'
import cookieParser from 'cookie-parser'
import cors from 'cors'

import apolloServer from './api/server'

const startServer = async () => {
    await apolloServer.start()

    const app = express()
    app.use(cookieParser())

    const corsOptions = {
        origin: true, // anyone can connect, simpler - for now - than specifying client host which will change with deployment
        credentials: true, // <-- REQUIRED backend setting
    }
    app.use(cors(corsOptions))

    apolloServer.applyMiddleware({
        app,
        cors: false, // very important so that express cors middleware settings are used
    })

    // await new Promise((resolve) => app.listen({ port: process.env.FRONTEND_API_PORT }, resolve))
    await new Promise(() => app.listen({ port: process.env.FRONTEND_API_PORT }))

    return { apolloServer, app }
}

startServer()
