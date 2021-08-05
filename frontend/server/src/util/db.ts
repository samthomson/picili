import bcrypt from 'bcrypt'

import * as Models from '../db/models'
import db from '../db/connection'

export const getUser = async (email: string, password: string): Promise<Models.UserInstance> => {
    const user = await Models.UserModel.findOne({
        where: {
            email,
        },
    })

    if (!user) {
        return undefined
    }

    let { password: hashedPassword } = user
    hashedPassword = hashedPassword.replace(/^\$2y(.+)$/i, '$2a$1')
    const passwordsMatch = await bcrypt.compare(password, hashedPassword)

    return passwordsMatch ? user : undefined
}

export const getUserFromId = async (id: string): Promise<Models.UserInstance> => {
    const user = await Models.UserModel.findOne({
        where: {
            id,
        },
    })

    return user
}

export const userWithEmailExists = async (email: string): Promise<boolean> => {
    const user = await Models.UserModel.findOne({
        where: {
            email,
        },
    })

    return !!user
}

export const createUser = async (email: string, password: string): Promise<Models.UserInstance> => {
    const salt = bcrypt.genSaltSync(10)
    const hashedPassword = await bcrypt.hash(password, salt)

    const user = await Models.UserModel.create({
        email,
        password: hashedPassword,
    })

    return user
}

export const overviewStats = async () => {
    const taskCount = await Models.TaskModel.count({
        // where: {
        //     email,
        // },
    })

    return {
        unprocessedTasksCount: taskCount,
    }
}
