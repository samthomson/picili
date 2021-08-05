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

interface TaskAttributes {
    id: string
    processor: string
}
type TaskCreationAttributes = Sequelize.Optional<TaskAttributes, 'id'>

export interface TaskInstance extends Sequelize.Model<TaskAttributes, TaskCreationAttributes>, TaskAttributes {
    createdAt?: Date
    updatedAt?: Date
}

export const TaskModel = db.define<TaskInstance>(
    'tasks',
    {
        processor: Sequelize.STRING,
    },
    {
        timestamps: true,
        underscored: true,
    },
)
