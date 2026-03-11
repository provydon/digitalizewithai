import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\DataViewController::dataShow
* @see app/Http/Controllers/DataViewController.php:37
* @route '/dashboard/api/data/{data}'
*/
export const dataShow = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dataShow.url(args, options),
    method: 'get',
})

dataShow.definition = {
    methods: ["get","head"],
    url: '/dashboard/api/data/{data}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DataViewController::dataShow
* @see app/Http/Controllers/DataViewController.php:37
* @route '/dashboard/api/data/{data}'
*/
dataShow.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return dataShow.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::dataShow
* @see app/Http/Controllers/DataViewController.php:37
* @route '/dashboard/api/data/{data}'
*/
dataShow.get = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dataShow.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DataViewController::dataShow
* @see app/Http/Controllers/DataViewController.php:37
* @route '/dashboard/api/data/{data}'
*/
dataShow.head = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dataShow.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DataViewController::originalFile
* @see app/Http/Controllers/DataViewController.php:149
* @route '/dashboard/api/data/{data}/original-file'
*/
export const originalFile = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: originalFile.url(args, options),
    method: 'get',
})

originalFile.definition = {
    methods: ["get","head"],
    url: '/dashboard/api/data/{data}/original-file',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DataViewController::originalFile
* @see app/Http/Controllers/DataViewController.php:149
* @route '/dashboard/api/data/{data}/original-file'
*/
originalFile.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return originalFile.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::originalFile
* @see app/Http/Controllers/DataViewController.php:149
* @route '/dashboard/api/data/{data}/original-file'
*/
originalFile.get = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: originalFile.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DataViewController::originalFile
* @see app/Http/Controllers/DataViewController.php:149
* @route '/dashboard/api/data/{data}/original-file'
*/
originalFile.head = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: originalFile.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DataViewController::update
* @see app/Http/Controllers/DataViewController.php:256
* @route '/dashboard/api/data/{data}'
*/
export const update = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/dashboard/api/data/{data}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\DataViewController::update
* @see app/Http/Controllers/DataViewController.php:256
* @route '/dashboard/api/data/{data}'
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
* @see app/Http/Controllers/DataViewController.php:256
* @route '/dashboard/api/data/{data}'
*/
update.patch = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\DataViewController::docPage
* @see app/Http/Controllers/DataViewController.php:206
* @route '/dashboard/api/data/{data}/doc-page'
*/
export const docPage = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: docPage.url(args, options),
    method: 'get',
})

docPage.definition = {
    methods: ["get","head"],
    url: '/dashboard/api/data/{data}/doc-page',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DataViewController::docPage
* @see app/Http/Controllers/DataViewController.php:206
* @route '/dashboard/api/data/{data}/doc-page'
*/
docPage.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return docPage.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::docPage
* @see app/Http/Controllers/DataViewController.php:206
* @route '/dashboard/api/data/{data}/doc-page'
*/
docPage.get = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: docPage.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DataViewController::docPage
* @see app/Http/Controllers/DataViewController.php:206
* @route '/dashboard/api/data/{data}/doc-page'
*/
docPage.head = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: docPage.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DataViewController::docContent
* @see app/Http/Controllers/DataViewController.php:233
* @route '/dashboard/api/data/{data}/doc-content'
*/
export const docContent = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: docContent.url(args, options),
    method: 'get',
})

docContent.definition = {
    methods: ["get","head"],
    url: '/dashboard/api/data/{data}/doc-content',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DataViewController::docContent
* @see app/Http/Controllers/DataViewController.php:233
* @route '/dashboard/api/data/{data}/doc-content'
*/
docContent.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return docContent.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::docContent
* @see app/Http/Controllers/DataViewController.php:233
* @route '/dashboard/api/data/{data}/doc-content'
*/
docContent.get = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: docContent.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DataViewController::docContent
* @see app/Http/Controllers/DataViewController.php:233
* @route '/dashboard/api/data/{data}/doc-content'
*/
docContent.head = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: docContent.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DataViewController::ask
* @see app/Http/Controllers/DataViewController.php:355
* @route '/dashboard/api/data/{data}/ask'
*/
export const ask = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ask.url(args, options),
    method: 'post',
})

ask.definition = {
    methods: ["post"],
    url: '/dashboard/api/data/{data}/ask',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DataViewController::ask
* @see app/Http/Controllers/DataViewController.php:355
* @route '/dashboard/api/data/{data}/ask'
*/
ask.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return ask.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::ask
* @see app/Http/Controllers/DataViewController.php:355
* @route '/dashboard/api/data/{data}/ask'
*/
ask.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ask.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DataViewController::askStream
* @see app/Http/Controllers/DataViewController.php:386
* @route '/dashboard/api/data/{data}/ask/stream'
*/
export const askStream = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: askStream.url(args, options),
    method: 'post',
})

askStream.definition = {
    methods: ["post"],
    url: '/dashboard/api/data/{data}/ask/stream',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DataViewController::askStream
* @see app/Http/Controllers/DataViewController.php:386
* @route '/dashboard/api/data/{data}/ask/stream'
*/
askStream.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return askStream.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::askStream
* @see app/Http/Controllers/DataViewController.php:386
* @route '/dashboard/api/data/{data}/ask/stream'
*/
askStream.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: askStream.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DataViewController::chartSuggestion
* @see app/Http/Controllers/DataViewController.php:483
* @route '/dashboard/api/data/{data}/chart-suggestion'
*/
export const chartSuggestion = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: chartSuggestion.url(args, options),
    method: 'post',
})

chartSuggestion.definition = {
    methods: ["post"],
    url: '/dashboard/api/data/{data}/chart-suggestion',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DataViewController::chartSuggestion
* @see app/Http/Controllers/DataViewController.php:483
* @route '/dashboard/api/data/{data}/chart-suggestion'
*/
chartSuggestion.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return chartSuggestion.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::chartSuggestion
* @see app/Http/Controllers/DataViewController.php:483
* @route '/dashboard/api/data/{data}/chart-suggestion'
*/
chartSuggestion.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: chartSuggestion.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DataViewController::updateDocContent
* @see app/Http/Controllers/DataViewController.php:275
* @route '/dashboard/api/data/{data}/doc-content'
*/
export const updateDocContent = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateDocContent.url(args, options),
    method: 'patch',
})

updateDocContent.definition = {
    methods: ["patch"],
    url: '/dashboard/api/data/{data}/doc-content',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\DataViewController::updateDocContent
* @see app/Http/Controllers/DataViewController.php:275
* @route '/dashboard/api/data/{data}/doc-content'
*/
updateDocContent.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return updateDocContent.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::updateDocContent
* @see app/Http/Controllers/DataViewController.php:275
* @route '/dashboard/api/data/{data}/doc-content'
*/
updateDocContent.patch = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateDocContent.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\DataViewController::savedChatsIndex
* @see app/Http/Controllers/DataViewController.php:572
* @route '/dashboard/api/data/{data}/saved-chats'
*/
export const savedChatsIndex = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: savedChatsIndex.url(args, options),
    method: 'get',
})

savedChatsIndex.definition = {
    methods: ["get","head"],
    url: '/dashboard/api/data/{data}/saved-chats',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DataViewController::savedChatsIndex
* @see app/Http/Controllers/DataViewController.php:572
* @route '/dashboard/api/data/{data}/saved-chats'
*/
savedChatsIndex.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return savedChatsIndex.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::savedChatsIndex
* @see app/Http/Controllers/DataViewController.php:572
* @route '/dashboard/api/data/{data}/saved-chats'
*/
savedChatsIndex.get = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: savedChatsIndex.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DataViewController::savedChatsIndex
* @see app/Http/Controllers/DataViewController.php:572
* @route '/dashboard/api/data/{data}/saved-chats'
*/
savedChatsIndex.head = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: savedChatsIndex.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DataViewController::savedChatStore
* @see app/Http/Controllers/DataViewController.php:594
* @route '/dashboard/api/data/{data}/saved-chats'
*/
export const savedChatStore = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: savedChatStore.url(args, options),
    method: 'post',
})

savedChatStore.definition = {
    methods: ["post"],
    url: '/dashboard/api/data/{data}/saved-chats',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DataViewController::savedChatStore
* @see app/Http/Controllers/DataViewController.php:594
* @route '/dashboard/api/data/{data}/saved-chats'
*/
savedChatStore.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return savedChatStore.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::savedChatStore
* @see app/Http/Controllers/DataViewController.php:594
* @route '/dashboard/api/data/{data}/saved-chats'
*/
savedChatStore.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: savedChatStore.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DataViewController::savedChatUpdate
* @see app/Http/Controllers/DataViewController.php:627
* @route '/dashboard/api/data/{data}/saved-chats/{saved_chat}'
*/
export const savedChatUpdate = (args: { data: number | { id: number }, saved_chat: number | { id: number } } | [data: number | { id: number }, saved_chat: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: savedChatUpdate.url(args, options),
    method: 'patch',
})

savedChatUpdate.definition = {
    methods: ["patch"],
    url: '/dashboard/api/data/{data}/saved-chats/{saved_chat}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\DataViewController::savedChatUpdate
* @see app/Http/Controllers/DataViewController.php:627
* @route '/dashboard/api/data/{data}/saved-chats/{saved_chat}'
*/
savedChatUpdate.url = (args: { data: number | { id: number }, saved_chat: number | { id: number } } | [data: number | { id: number }, saved_chat: number | { id: number } ], options?: RouteQueryOptions) => {
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

    return savedChatUpdate.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace('{saved_chat}', parsedArgs.saved_chat.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::savedChatUpdate
* @see app/Http/Controllers/DataViewController.php:627
* @route '/dashboard/api/data/{data}/saved-chats/{saved_chat}'
*/
savedChatUpdate.patch = (args: { data: number | { id: number }, saved_chat: number | { id: number } } | [data: number | { id: number }, saved_chat: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: savedChatUpdate.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\DataViewController::savedChatDestroy
* @see app/Http/Controllers/DataViewController.php:659
* @route '/dashboard/api/data/{data}/saved-chats/{saved_chat}'
*/
export const savedChatDestroy = (args: { data: number | { id: number }, saved_chat: number | { id: number } } | [data: number | { id: number }, saved_chat: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: savedChatDestroy.url(args, options),
    method: 'delete',
})

savedChatDestroy.definition = {
    methods: ["delete"],
    url: '/dashboard/api/data/{data}/saved-chats/{saved_chat}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\DataViewController::savedChatDestroy
* @see app/Http/Controllers/DataViewController.php:659
* @route '/dashboard/api/data/{data}/saved-chats/{saved_chat}'
*/
savedChatDestroy.url = (args: { data: number | { id: number }, saved_chat: number | { id: number } } | [data: number | { id: number }, saved_chat: number | { id: number } ], options?: RouteQueryOptions) => {
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

    return savedChatDestroy.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace('{saved_chat}', parsedArgs.saved_chat.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::savedChatDestroy
* @see app/Http/Controllers/DataViewController.php:659
* @route '/dashboard/api/data/{data}/saved-chats/{saved_chat}'
*/
savedChatDestroy.delete = (args: { data: number | { id: number }, saved_chat: number | { id: number } } | [data: number | { id: number }, saved_chat: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: savedChatDestroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\DataViewController::savedChartsIndex
* @see app/Http/Controllers/DataViewController.php:671
* @route '/dashboard/api/data/{data}/saved-charts'
*/
export const savedChartsIndex = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: savedChartsIndex.url(args, options),
    method: 'get',
})

savedChartsIndex.definition = {
    methods: ["get","head"],
    url: '/dashboard/api/data/{data}/saved-charts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DataViewController::savedChartsIndex
* @see app/Http/Controllers/DataViewController.php:671
* @route '/dashboard/api/data/{data}/saved-charts'
*/
savedChartsIndex.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return savedChartsIndex.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::savedChartsIndex
* @see app/Http/Controllers/DataViewController.php:671
* @route '/dashboard/api/data/{data}/saved-charts'
*/
savedChartsIndex.get = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: savedChartsIndex.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DataViewController::savedChartsIndex
* @see app/Http/Controllers/DataViewController.php:671
* @route '/dashboard/api/data/{data}/saved-charts'
*/
savedChartsIndex.head = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: savedChartsIndex.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DataViewController::savedChartStore
* @see app/Http/Controllers/DataViewController.php:693
* @route '/dashboard/api/data/{data}/saved-charts'
*/
export const savedChartStore = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: savedChartStore.url(args, options),
    method: 'post',
})

savedChartStore.definition = {
    methods: ["post"],
    url: '/dashboard/api/data/{data}/saved-charts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DataViewController::savedChartStore
* @see app/Http/Controllers/DataViewController.php:693
* @route '/dashboard/api/data/{data}/saved-charts'
*/
savedChartStore.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return savedChartStore.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::savedChartStore
* @see app/Http/Controllers/DataViewController.php:693
* @route '/dashboard/api/data/{data}/saved-charts'
*/
savedChartStore.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: savedChartStore.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DataViewController::savedChartDestroy
* @see app/Http/Controllers/DataViewController.php:726
* @route '/dashboard/api/data/{data}/saved-charts/{saved_chart}'
*/
export const savedChartDestroy = (args: { data: number | { id: number }, saved_chart: number | { id: number } } | [data: number | { id: number }, saved_chart: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: savedChartDestroy.url(args, options),
    method: 'delete',
})

savedChartDestroy.definition = {
    methods: ["delete"],
    url: '/dashboard/api/data/{data}/saved-charts/{saved_chart}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\DataViewController::savedChartDestroy
* @see app/Http/Controllers/DataViewController.php:726
* @route '/dashboard/api/data/{data}/saved-charts/{saved_chart}'
*/
savedChartDestroy.url = (args: { data: number | { id: number }, saved_chart: number | { id: number } } | [data: number | { id: number }, saved_chart: number | { id: number } ], options?: RouteQueryOptions) => {
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

    return savedChartDestroy.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace('{saved_chart}', parsedArgs.saved_chart.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DataViewController::savedChartDestroy
* @see app/Http/Controllers/DataViewController.php:726
* @route '/dashboard/api/data/{data}/saved-charts/{saved_chart}'
*/
savedChartDestroy.delete = (args: { data: number | { id: number }, saved_chart: number | { id: number } } | [data: number | { id: number }, saved_chart: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: savedChartDestroy.url(args, options),
    method: 'delete',
})

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

const DataViewController = { dataShow, originalFile, update, docPage, docContent, ask, askStream, chartSuggestion, updateDocContent, savedChatsIndex, savedChatStore, savedChatUpdate, savedChatDestroy, savedChartsIndex, savedChartStore, savedChartDestroy, show }

export default DataViewController