export namespace API {
    export namespace Response {
        export type Overview = {
            unprocessedTasksCount: number
        }
        export type Auth = {
            token?: string
            error?: string
        }
    }
}
