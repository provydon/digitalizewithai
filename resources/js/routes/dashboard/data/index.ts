import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\DataViewController::show
* @see app/Http/Controllers/DataViewController.php:21
* @route '/dashboard/data/{data}'
*/
export const show = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/dashboard/data/{data}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DataViewController::show
* @see app/Http/Controllers/DataViewController.php:21
* @route '/dashboard/data/{data}'
*/
show.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::show
* @see app/Http/Controllers/DataViewController.php:21
* @route '/dashboard/data/{data}'
*/
show.get = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DataViewController::show
* @see app/Http/Controllers/DataViewController.php:21
* @route '/dashboard/data/{data}'
*/
show.head = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

const data = {
    show: Object.assign(show, show),
}

export default data