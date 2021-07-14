import bcrypt from 'bcrypt'

import * as Models from '../db/models'
import db from '../db/connection'

export const getUser = async (email: string, password: string): Promise<Models.UserInstance> => {

    const user = await Models.UserModel.findOne({
        where: {
            email
        },
    })

    if (!user) {
        return undefined
    }

    let { password: hashedPassword } = user
    hashedPassword = hashedPassword.replace(/^\$2y(.+)$/i, '$2a$1');
    const passwordsMatch = await bcrypt.compare(password, hashedPassword)

    return passwordsMatch ? user : undefined
}