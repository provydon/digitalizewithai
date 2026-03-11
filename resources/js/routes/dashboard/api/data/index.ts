import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
import docContentCfa3f4 from './doc-content'
import ask4f0227 from './ask'
import rows from './rows'
import savedChats from './saved-chats'
import savedCharts from './saved-charts'
/**
* @see \App\Http\Controllers\Api\DigitalizeController::appendRows
* @see app/Http/Controllers/Api/DigitalizeController.php:279
* @route '/dashboard/api/data/{data}/append-rows'
*/
export const appendRows = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: appendRows.url(args, options),
    method: 'post',
})

appendRows.definition = {
    methods: ["post"],
    url: '/dashboard/api/data/{data}/append-rows',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\DigitalizeController::appendRows
* @see app/Http/Controllers/Api/DigitalizeController.php:279
* @route '/dashboard/api/data/{data}/append-rows'
*/
appendRows.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return appendRows.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\DigitalizeController::appendRows
* @see app/Http/Controllers/Api/DigitalizeController.php:279
* @route '/dashboard/api/data/{data}/append-rows'
*/
appendRows.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: appendRows.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::appendDoc
* @see app/Http/Controllers/Api/DigitalizeController.php:392
* @route '/dashboard/api/data/{data}/append-doc'
*/
export const appendDoc = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: appendDoc.url(args, options),
    method: 'post',
})

appendDoc.definition = {
    methods: ["post"],
    url: '/dashboard/api/data/{data}/append-doc',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\DigitalizeController::appendDoc
* @see app/Http/Controllers/Api/DigitalizeController.php:392
* @route '/dashboard/api/data/{data}/append-doc'
*/
appendDoc.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return appendDoc.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\DigitalizeController::appendDoc
* @see app/Http/Controllers/Api/DigitalizeController.php:392
* @route '/dashboard/api/data/{data}/append-doc'
*/
appendDoc.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: appendDoc.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:25
* @route '/dashboard/api/data'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/dashboard/api/data',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:25
* @route '/dashboard/api/data'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:25
* @route '/dashboard/api/data'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::index
* @see app/Http/Controllers/DashboardController.php:25
* @route '/dashboard/api/data'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DashboardController::destroy
* @see app/Http/Controllers/DashboardController.php:80
* @route '/dashboard/api/data/{data}'
*/
export const destroy = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/dashboard/api/data/{data}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\DashboardController::destroy
* @see app/Http/Controllers/DashboardController.php:80
* @route '/dashboard/api/data/{data}'
*/
destroy.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return destroy.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DashboardController::destroy
* @see app/Http/Controllers/DashboardController.php:80
* @route '/dashboard/api/data/{data}'
*/
destroy.delete = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\DataViewController::show
* @see app/Http/Controllers/DataViewController.php:37
* @route '/dashboard/api/data/{data}'
*/
export const show = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/dashboard/api/data/{data}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DataViewController::show
* @see app/Http/Controllers/DataViewController.php:37
* @route '/dashboard/api/data/{data}'
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
* @see app/Http/Controllers/DataViewController.php:37
* @route '/dashboard/api/data/{data}'
*/
show.get = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DataViewController::show
* @see app/Http/Controllers/DataViewController.php:37
* @route '/dashboard/api/data/{data}'
*/
show.head = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
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

const data = {
    appendRows: Object.assign(appendRows, appendRows),
    appendDoc: Object.assign(appendDoc, appendDoc),
    index: Object.assign(index, index),
    destroy: Object.assign(destroy, destroy),
    show: Object.assign(show, show),
    originalFile: Object.assign(originalFile, originalFile),
    update: Object.assign(update, update),
    docPage: Object.assign(docPage, docPage),
    docContent: Object.assign(docContent, docContentCfa3f4),
    ask: Object.assign(ask, ask4f0227),
    chartSuggestion: Object.assign(chartSuggestion, chartSuggestion),
    rows: Object.assign(rows, rows),
    savedChats: Object.assign(savedChats, savedChats),
    savedCharts: Object.assign(savedCharts, savedCharts),
}

export default data