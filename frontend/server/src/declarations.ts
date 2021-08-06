export namespace API {
    export namespace Response {
        export type Auth = {
            token?: string
            error?: string
        }
        export type Overview = {
            unprocessedTasksCount: number
        }
        type QueueSummary = {
            processor: string
            taskCount: number
            oldest: string
        }
        export type Queue = {
            unprocessedTasksCount: number
            queueSummaries: QueueSummary[]
        }
    }
}
