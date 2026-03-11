import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\DataViewController::update
* @see app/Http/Controllers/DataViewController.php:275
* @route '/dashboard/api/data/{data}/doc-content'
*/
export const update = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/dashboard/api/data/{data}/doc-content',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\DataViewController::update
* @see app/Http/Controllers/DataViewController.php:275
* @route '/dashboard/api/data/{data}/doc-content'
*/
update.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return update.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::update
* @see app/Http/Controllers/DataViewController.php:275
* @route '/dashboard/api/data/{data}/doc-content'
*/
update.patch = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

const docContent = {
    update: Object.assign(update, update),
}

export default docContent