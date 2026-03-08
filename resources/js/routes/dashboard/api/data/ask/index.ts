import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\DataViewController::stream
* @see app/Http/Controllers/DataViewController.php:314
* @route '/dashboard/api/data/{data}/ask/stream'
*/
export const stream = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: stream.url(args, options),
    method: 'post',
})

stream.definition = {
    methods: ["post"],
    url: '/dashboard/api/data/{data}/ask/stream',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DataViewController::stream
* @see app/Http/Controllers/DataViewController.php:314
* @route '/dashboard/api/data/{data}/ask/stream'
*/
stream.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return stream.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::stream
* @see app/Http/Controllers/DataViewController.php:314
* @route '/dashboard/api/data/{data}/ask/stream'
*/
stream.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: stream.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DataViewController::stream
* @see app/Http/Controllers/DataViewController.php:314
* @route '/dashboard/api/data/{data}/ask/stream'
*/
const streamForm = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: stream.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DataViewController::stream
* @see app/Http/Controllers/DataViewController.php:314
* @route '/dashboard/api/data/{data}/ask/stream'
*/
streamForm.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: stream.url(args, options),
    method: 'post',
})

stream.form = streamForm

const ask = {
    stream: Object.assign(stream, stream),
}

export default ask