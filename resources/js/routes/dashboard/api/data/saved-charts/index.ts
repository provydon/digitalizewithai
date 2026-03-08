import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\DataViewController::index
* @see app/Http/Controllers/DataViewController.php:599
* @route '/dashboard/api/data/{data}/saved-charts'
*/
export const index = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/dashboard/api/data/{data}/saved-charts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DataViewController::index
* @see app/Http/Controllers/DataViewController.php:599
* @route '/dashboard/api/data/{data}/saved-charts'
*/
index.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return index.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::index
* @see app/Http/Controllers/DataViewController.php:599
* @route '/dashboard/api/data/{data}/saved-charts'
*/
index.get = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DataViewController::index
* @see app/Http/Controllers/DataViewController.php:599
* @route '/dashboard/api/data/{data}/saved-charts'
*/
index.head = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DataViewController::index
* @see app/Http/Controllers/DataViewController.php:599
* @route '/dashboard/api/data/{data}/saved-charts'
*/
const indexForm = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DataViewController::index
* @see app/Http/Controllers/DataViewController.php:599
* @route '/dashboard/api/data/{data}/saved-charts'
*/
indexForm.get = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DataViewController::index
* @see app/Http/Controllers/DataViewController.php:599
* @route '/dashboard/api/data/{data}/saved-charts'
*/
indexForm.head = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\DataViewController::store
* @see app/Http/Controllers/DataViewController.php:621
* @route '/dashboard/api/data/{data}/saved-charts'
*/
export const store = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/dashboard/api/data/{data}/saved-charts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DataViewController::store
* @see app/Http/Controllers/DataViewController.php:621
* @route '/dashboard/api/data/{data}/saved-charts'
*/
store.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return store.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::store
* @see app/Http/Controllers/DataViewController.php:621
* @route '/dashboard/api/data/{data}/saved-charts'
*/
store.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DataViewController::store
* @see app/Http/Controllers/DataViewController.php:621
* @route '/dashboard/api/data/{data}/saved-charts'
*/
const storeForm = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DataViewController::store
* @see app/Http/Controllers/DataViewController.php:621
* @route '/dashboard/api/data/{data}/saved-charts'
*/
storeForm.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\DataViewController::destroy
* @see app/Http/Controllers/DataViewController.php:654
* @route '/dashboard/api/data/{data}/saved-charts/{saved_chart}'
*/
export const destroy = (args: { data: number | { id: number }, saved_chart: number | { id: number } } | [data: number | { id: number }, saved_chart: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/dashboard/api/data/{data}/saved-charts/{saved_chart}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\DataViewController::destroy
* @see app/Http/Controllers/DataViewController.php:654
* @route '/dashboard/api/data/{data}/saved-charts/{saved_chart}'
*/
destroy.url = (args: { data: number | { id: number }, saved_chart: number | { id: number } } | [data: number | { id: number }, saved_chart: number | { id: number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            data: args[0],
            saved_chart: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        data: typeof args.data === 'object'
        ? args.data.id
        : args.data,
        saved_chart: typeof args.saved_chart === 'object'
        ? args.saved_chart.id
        : args.saved_chart,
    }

    return destroy.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace('{saved_chart}', parsedArgs.saved_chart.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::destroy
* @see app/Http/Controllers/DataViewController.php:654
* @route '/dashboard/api/data/{data}/saved-charts/{saved_chart}'
*/
destroy.delete = (args: { data: number | { id: number }, saved_chart: number | { id: number } } | [data: number | { id: number }, saved_chart: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\DataViewController::destroy
* @see app/Http/Controllers/DataViewController.php:654
* @route '/dashboard/api/data/{data}/saved-charts/{saved_chart}'
*/
const destroyForm = (args: { data: number | { id: number }, saved_chart: number | { id: number } } | [data: number | { id: number }, saved_chart: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DataViewController::destroy
* @see app/Http/Controllers/DataViewController.php:654
* @route '/dashboard/api/data/{data}/saved-charts/{saved_chart}'
*/
destroyForm.delete = (args: { data: number | { id: number }, saved_chart: number | { id: number } } | [data: number | { id: number }, saved_chart: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const savedCharts = {
    index: Object.assign(index, index),
    store: Object.assign(store, store),
    destroy: Object.assign(destroy, destroy),
}

export default savedCharts