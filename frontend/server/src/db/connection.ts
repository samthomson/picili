import Sequelize from 'sequelize'

const { MYSQL_HOST, MYSQL_PASSWORD, MYSQL_USER, DATABASE_NAME, DATABASE_NAME_TESTING, NODE_ENV } = process.env

const dbConfig = {
    host: MYSQL_HOST,
    port: 3306,
    username: MYSQL_USER,
    password: MYSQL_PASSWORD,
    dialect: 'mysql',
    dialectOptions: {
        charset: 'utf8',
        decimalNumbers: true,
    },
    pool: {
        max: 12, // default 3306
        acquire: 10000, // default 60000
    },
    // logging: (message: string, data: Record<string, unknown>): void => {
    // 	Logger.silly(message, {
    // 		type: 'sequelize',
    // 		data,
    // 	})
    // },
}
const dbName = NODE_ENV === 'testing' ? DATABASE_NAME_TESTING : DATABASE_NAME
export const dbConnection = {
    ...dbConfig,
    database: dbName,
}

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
const db = new Sequelize(dbConnection)

db.authenticate().catch(() => {
    console.error('Could not connect to database')
    // Logger.warn('Could not connect to database!')
})

export default db
