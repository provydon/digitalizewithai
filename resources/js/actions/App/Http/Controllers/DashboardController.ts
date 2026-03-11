import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:13
* @route '/dashboard'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:13
* @route '/dashboard'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:13
* @route '/dashboard'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:13
* @route '/dashboard'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DashboardController::dataIndex
* @see app/Http/Controllers/DashboardController.php:25
* @route '/dashboard/api/data'
*/
export const dataIndex = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dataIndex.url(options),
    method: 'get',
})

dataIndex.definition = {
    methods: ["get","head"],
    url: '/dashboard/api/data',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DashboardController::dataIndex
* @see app/Http/Controllers/DashboardController.php:25
* @route '/dashboard/api/data'
*/
dataIndex.url = (options?: RouteQueryOptions) => {
    return dataIndex.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DashboardController::dataIndex
* @see app/Http/Controllers/DashboardController.php:25
* @route '/dashboard/api/data'
*/
dataIndex.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dataIndex.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::dataIndex
* @see app/Http/Controllers/DashboardController.php:25
* @route '/dashboard/api/data'
*/
dataIndex.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dataIndex.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DashboardController::destroyData
* @see app/Http/Controllers/DashboardController.php:80
* @route '/dashboard/api/data/{data}'
*/
export const destroyData = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyData.url(args, options),
    method: 'delete',
})

destroyData.definition = {
    methods: ["delete"],
    url: '/dashboard/api/data/{data}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\DashboardController::destroyData
* @see app/Http/Controllers/DashboardController.php:80
* @route '/dashboard/api/data/{data}'
*/
destroyData.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { data: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { data: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            data: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        data: typeof args.data === 'object'
        ? args.data.id
        : args.data,
    }

    return destroyData.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DashboardController::destroyData
* @see app/Http/Controllers/DashboardController.php:80
* @route '/dashboard/api/data/{data}'
*/
destroyData.delete = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyData.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\DashboardController::dataPage
* @see app/Http/Controllers/DashboardController.php:19
* @route '/data'
*/
export const dataPage = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dataPage.url(options),
    method: 'get',
})

dataPage.definition = {
    methods: ["get","head"],
    url: '/data',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DashboardController::dataPage
* @see app/Http/Controllers/DashboardController.php:19
* @route '/data'
*/
dataPage.url = (options?: RouteQueryOptions) => {
    return dataPage.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DashboardController::dataPage
* @see app/Http/Controllers/DashboardController.php:19
* @route '/data'
*/
dataPage.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dataPage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::dataPage
* @see app/Http/Controllers/DashboardController.php:19
* @route '/data'
*/
dataPage.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dataPage.url(options),
    method: 'head',
})

const DashboardController = { index, dataIndex, destroyData, dataPage }

export default DashboardController