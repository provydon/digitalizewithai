import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\DataViewController::index
* @see app/Http/Controllers/DataViewController.php:500
* @route '/dashboard/api/data/{data}/saved-chats'
*/
export const index = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/dashboard/api/data/{data}/saved-chats',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DataViewController::index
* @see app/Http/Controllers/DataViewController.php:500
* @route '/dashboard/api/data/{data}/saved-chats'
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
* @see app/Http/Controllers/DataViewController.php:500
* @route '/dashboard/api/data/{data}/saved-chats'
*/
index.get = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DataViewController::index
* @see app/Http/Controllers/DataViewController.php:500
* @route '/dashboard/api/data/{data}/saved-chats'
*/
index.head = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DataViewController::index
* @see app/Http/Controllers/DataViewController.php:500
* @route '/dashboard/api/data/{data}/saved-chats'
*/
const indexForm = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DataViewController::index
* @see app/Http/Controllers/DataViewController.php:500
* @route '/dashboard/api/data/{data}/saved-chats'
*/
indexForm.get = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DataViewController::index
* @see app/Http/Controllers/DataViewController.php:500
* @route '/dashboard/api/data/{data}/saved-chats'
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
* @see app/Http/Controllers/DataViewController.php:522
* @route '/dashboard/api/data/{data}/saved-chats'
*/
export const store = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/dashboard/api/data/{data}/saved-chats',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DataViewController::store
* @see app/Http/Controllers/DataViewController.php:522
* @route '/dashboard/api/data/{data}/saved-chats'
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
* @see app/Http/Controllers/DataViewController.php:522
* @route '/dashboard/api/data/{data}/saved-chats'
*/
store.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DataViewController::store
* @see app/Http/Controllers/DataViewController.php:522
* @route '/dashboard/api/data/{data}/saved-chats'
*/
const storeForm = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DataViewController::store
* @see app/Http/Controllers/DataViewController.php:522
* @route '/dashboard/api/data/{data}/saved-chats'
*/
storeForm.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\DataViewController::update
* @see app/Http/Controllers/DataViewController.php:555
* @route '/dashboard/api/data/{data}/saved-chats/{saved_chat}'
*/
export const update = (args: { data: number | { id: number }, saved_chat: number | { id: number } } | [data: number | { id: number }, saved_chat: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/dashboard/api/data/{data}/saved-chats/{saved_chat}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\DataViewController::update
* @see app/Http/Controllers/DataViewController.php:555
* @route '/dashboard/api/data/{data}/saved-chats/{saved_chat}'
*/
update.url = (args: { data: number | { id: number }, saved_chat: number | { id: number } } | [data: number | { id: number }, saved_chat: number | { id: number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            data: args[0],
            saved_chat: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        data: typeof args.data === 'object'
        ? args.data.id
        : args.data,
        saved_chat: typeof args.saved_chat === 'object'
        ? args.saved_chat.id
        : args.saved_chat,
    }

    return update.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace('{saved_chat}', parsedArgs.saved_chat.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::update
* @see app/Http/Controllers/DataViewController.php:555
* @route '/dashboard/api/data/{data}/saved-chats/{saved_chat}'
*/
update.patch = (args: { data: number | { id: number }, saved_chat: number | { id: number } } | [data: number | { id: number }, saved_chat: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\DataViewController::update
* @see app/Http/Controllers/DataViewController.php:555
* @route '/dashboard/api/data/{data}/saved-chats/{saved_chat}'
*/
const updateForm = (args: { data: number | { id: number }, saved_chat: number | { id: number } } | [data: number | { id: number }, saved_chat: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DataViewController::update
* @see app/Http/Controllers/DataViewController.php:555
* @route '/dashboard/api/data/{data}/saved-chats/{saved_chat}'
*/
updateForm.patch = (args: { data: number | { id: number }, saved_chat: number | { id: number } } | [data: number | { id: number }, saved_chat: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\DataViewController::destroy
* @see app/Http/Controllers/DataViewController.php:587
* @route '/dashboard/api/data/{data}/saved-chats/{saved_chat}'
*/
export const destroy = (args: { data: number | { id: number }, saved_chat: number | { id: number } } | [data: number | { id: number }, saved_chat: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/dashboard/api/data/{data}/saved-chats/{saved_chat}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\DataViewController::destroy
* @see app/Http/Controllers/DataViewController.php:587
* @route '/dashboard/api/data/{data}/saved-chats/{saved_chat}'
*/
destroy.url = (args: { data: number | { id: number }, saved_chat: number | { id: number } } | [data: number | { id: number }, saved_chat: number | { id: number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            data: args[0],
            saved_chat: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        data: typeof args.data === 'object'
        ? args.data.id
        : args.data,
        saved_chat: typeof args.saved_chat === 'object'
        ? args.saved_chat.id
        : args.saved_chat,
    }

    return destroy.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace('{saved_chat}', parsedArgs.saved_chat.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::destroy
* @see app/Http/Controllers/DataViewController.php:587
* @route '/dashboard/api/data/{data}/saved-chats/{saved_chat}'
*/
destroy.delete = (args: { data: number | { id: number }, saved_chat: number | { id: number } } | [data: number | { id: number }, saved_chat: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\DataViewController::destroy
* @see app/Http/Controllers/DataViewController.php:587
* @route '/dashboard/api/data/{data}/saved-chats/{saved_chat}'
*/
const destroyForm = (args: { data: number | { id: number }, saved_chat: number | { id: number } } | [data: number | { id: number }, saved_chat: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see app/Http/Controllers/DataViewController.php:587
* @route '/dashboard/api/data/{data}/saved-chats/{saved_chat}'
*/
destroyForm.delete = (args: { data: number | { id: number }, saved_chat: number | { id: number } } | [data: number | { id: number }, saved_chat: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const savedChats = {
    index: Object.assign(index, index),
    store: Object.assign(store, store),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
}

export default savedChats