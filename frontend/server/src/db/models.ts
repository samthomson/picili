import * as Sequelize from 'sequelize'
import db from './connection'

interface UserAttributes {
    id: string
    email: string
    password: string
}
type UserCreationAttributes = Sequelize.Optional<UserAttributes, 'id'>

export interface UserInstance extends Sequelize.Model<UserAttributes, UserCreationAttributes>, UserAttributes {
    createdAt?: Date
    updatedAt?: Date
}

export const UserModel = db.define<UserInstance>(
    'users',
    {
        email: Sequelize.STRING,
        password: Sequelize.STRING,
    },
    {
        timestamps: true,
        underscored: true,
    },
)
