import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
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
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:13
* @route '/dashboard'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:13
* @route '/dashboard'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:13
* @route '/dashboard'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

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
* @see \App\Http\Controllers\DashboardController::dataIndex
* @see app/Http/Controllers/DashboardController.php:25
* @route '/dashboard/api/data'
*/
const dataIndexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dataIndex.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::dataIndex
* @see app/Http/Controllers/DashboardController.php:25
* @route '/dashboard/api/data'
*/
dataIndexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dataIndex.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::dataIndex
* @see app/Http/Controllers/DashboardController.php:25
* @route '/dashboard/api/data'
*/
dataIndexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dataIndex.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

dataIndex.form = dataIndexForm

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
* @see \App\Http\Controllers\DashboardController::destroyData
* @see app/Http/Controllers/DashboardController.php:80
* @route '/dashboard/api/data/{data}'
*/
const destroyDataForm = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroyData.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DashboardController::destroyData
* @see app/Http/Controllers/DashboardController.php:80
* @route '/dashboard/api/data/{data}'
*/
destroyDataForm.delete = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroyData.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroyData.form = destroyDataForm

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

/**
* @see \App\Http\Controllers\DashboardController::dataPage
* @see app/Http/Controllers/DashboardController.php:19
* @route '/data'
*/
const dataPageForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dataPage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::dataPage
* @see app/Http/Controllers/DashboardController.php:19
* @route '/data'
*/
dataPageForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dataPage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::dataPage
* @see app/Http/Controllers/DashboardController.php:19
* @route '/data'
*/
dataPageForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dataPage.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

dataPage.form = dataPageForm

const DashboardController = { index, dataIndex, destroyData, dataPage }

export default DashboardController