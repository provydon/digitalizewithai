import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\DataTableRowsController::index
* @see app/Http/Controllers/DataTableRowsController.php:26
* @route '/dashboard/api/data/{data}/rows'
*/
export const index = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/dashboard/api/data/{data}/rows',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DataTableRowsController::index
* @see app/Http/Controllers/DataTableRowsController.php:26
* @route '/dashboard/api/data/{data}/rows'
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
* @see \App\Http\Controllers\DataTableRowsController::index
* @see app/Http/Controllers/DataTableRowsController.php:26
* @route '/dashboard/api/data/{data}/rows'
*/
index.get = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DataTableRowsController::index
* @see app/Http/Controllers/DataTableRowsController.php:26
* @route '/dashboard/api/data/{data}/rows'
*/
index.head = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DataTableRowsController::index
* @see app/Http/Controllers/DataTableRowsController.php:26
* @route '/dashboard/api/data/{data}/rows'
*/
const indexForm = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DataTableRowsController::index
* @see app/Http/Controllers/DataTableRowsController.php:26
* @route '/dashboard/api/data/{data}/rows'
*/
indexForm.get = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DataTableRowsController::index
* @see app/Http/Controllers/DataTableRowsController.php:26
* @route '/dashboard/api/data/{data}/rows'
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
* @see \App\Http\Controllers\DataTableRowsController::store
* @see app/Http/Controllers/DataTableRowsController.php:67
* @route '/dashboard/api/data/{data}/rows'
*/
export const store = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/dashboard/api/data/{data}/rows',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DataTableRowsController::store
* @see app/Http/Controllers/DataTableRowsController.php:67
* @route '/dashboard/api/data/{data}/rows'
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
* @see \App\Http\Controllers\DataTableRowsController::store
* @see app/Http/Controllers/DataTableRowsController.php:67
* @route '/dashboard/api/data/{data}/rows'
*/
store.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DataTableRowsController::store
* @see app/Http/Controllers/DataTableRowsController.php:67
* @route '/dashboard/api/data/{data}/rows'
*/
const storeForm = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DataTableRowsController::store
* @see app/Http/Controllers/DataTableRowsController.php:67
* @route '/dashboard/api/data/{data}/rows'
*/
storeForm.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\DataTableRowsController::update
* @see app/Http/Controllers/DataTableRowsController.php:104
* @route '/dashboard/api/data/{data}/rows/{data_table_row}'
*/
export const update = (args: { data: number | { id: number }, data_table_row: number | { id: number } } | [data: number | { id: number }, data_table_row: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/dashboard/api/data/{data}/rows/{data_table_row}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\DataTableRowsController::update
* @see app/Http/Controllers/DataTableRowsController.php:104
* @route '/dashboard/api/data/{data}/rows/{data_table_row}'
*/
update.url = (args: { data: number | { id: number }, data_table_row: number | { id: number } } | [data: number | { id: number }, data_table_row: number | { id: number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            data: args[0],
            data_table_row: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        data: typeof args.data === 'object'
        ? args.data.id
        : args.data,
        data_table_row: typeof args.data_table_row === 'object'
        ? args.data_table_row.id
        : args.data_table_row,
    }

    return update.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace('{data_table_row}', parsedArgs.data_table_row.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataTableRowsController::update
* @see app/Http/Controllers/DataTableRowsController.php:104
* @route '/dashboard/api/data/{data}/rows/{data_table_row}'
*/
update.patch = (args: { data: number | { id: number }, data_table_row: number | { id: number } } | [data: number | { id: number }, data_table_row: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\DataTableRowsController::update
* @see app/Http/Controllers/DataTableRowsController.php:104
* @route '/dashboard/api/data/{data}/rows/{data_table_row}'
*/
const updateForm = (args: { data: number | { id: number }, data_table_row: number | { id: number } } | [data: number | { id: number }, data_table_row: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DataTableRowsController::update
* @see app/Http/Controllers/DataTableRowsController.php:104
* @route '/dashboard/api/data/{data}/rows/{data_table_row}'
*/
updateForm.patch = (args: { data: number | { id: number }, data_table_row: number | { id: number } } | [data: number | { id: number }, data_table_row: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\DataTableRowsController::destroy
* @see app/Http/Controllers/DataTableRowsController.php:126
* @route '/dashboard/api/data/{data}/rows/{data_table_row}'
*/
export const destroy = (args: { data: number | { id: number }, data_table_row: number | { id: number } } | [data: number | { id: number }, data_table_row: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/dashboard/api/data/{data}/rows/{data_table_row}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\DataTableRowsController::destroy
* @see app/Http/Controllers/DataTableRowsController.php:126
* @route '/dashboard/api/data/{data}/rows/{data_table_row}'
*/
destroy.url = (args: { data: number | { id: number }, data_table_row: number | { id: number } } | [data: number | { id: number }, data_table_row: number | { id: number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            data: args[0],
            data_table_row: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        data: typeof args.data === 'object'
        ? args.data.id
        : args.data,
        data_table_row: typeof args.data_table_row === 'object'
        ? args.data_table_row.id
        : args.data_table_row,
    }

    return destroy.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace('{data_table_row}', parsedArgs.data_table_row.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataTableRowsController::destroy
* @see app/Http/Controllers/DataTableRowsController.php:126
* @route '/dashboard/api/data/{data}/rows/{data_table_row}'
*/
destroy.delete = (args: { data: number | { id: number }, data_table_row: number | { id: number } } | [data: number | { id: number }, data_table_row: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\DataTableRowsController::destroy
* @see app/Http/Controllers/DataTableRowsController.php:126
* @route '/dashboard/api/data/{data}/rows/{data_table_row}'
*/
const destroyForm = (args: { data: number | { id: number }, data_table_row: number | { id: number } } | [data: number | { id: number }, data_table_row: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DataTableRowsController::destroy
* @see app/Http/Controllers/DataTableRowsController.php:126
* @route '/dashboard/api/data/{data}/rows/{data_table_row}'
*/
destroyForm.delete = (args: { data: number | { id: number }, data_table_row: number | { id: number } } | [data: number | { id: number }, data_table_row: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const DataTableRowsController = { index, store, update, destroy }

export default DataTableRowsController