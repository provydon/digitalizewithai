import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\DigitalizeController::store
* @see app/Http/Controllers/Api/DigitalizeController.php:33
* @route '/api/digitalize'
*/
const stored864a3a2374b8ccf16b0191fdf968511 = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: stored864a3a2374b8ccf16b0191fdf968511.url(options),
    method: 'post',
})

stored864a3a2374b8ccf16b0191fdf968511.definition = {
    methods: ["post"],
    url: '/api/digitalize',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\DigitalizeController::store
* @see app/Http/Controllers/Api/DigitalizeController.php:33
* @route '/api/digitalize'
*/
stored864a3a2374b8ccf16b0191fdf968511.url = (options?: RouteQueryOptions) => {
    return stored864a3a2374b8ccf16b0191fdf968511.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\DigitalizeController::store
* @see app/Http/Controllers/Api/DigitalizeController.php:33
* @route '/api/digitalize'
*/
stored864a3a2374b8ccf16b0191fdf968511.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: stored864a3a2374b8ccf16b0191fdf968511.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::store
* @see app/Http/Controllers/Api/DigitalizeController.php:33
* @route '/api/digitalize'
*/
const stored864a3a2374b8ccf16b0191fdf968511Form = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: stored864a3a2374b8ccf16b0191fdf968511.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::store
* @see app/Http/Controllers/Api/DigitalizeController.php:33
* @route '/api/digitalize'
*/
stored864a3a2374b8ccf16b0191fdf968511Form.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: stored864a3a2374b8ccf16b0191fdf968511.url(options),
    method: 'post',
})

stored864a3a2374b8ccf16b0191fdf968511.form = stored864a3a2374b8ccf16b0191fdf968511Form
/**
* @see \App\Http\Controllers\Api\DigitalizeController::store
* @see app/Http/Controllers/Api/DigitalizeController.php:33
* @route '/dashboard/digitalize'
*/
const storecb2017140d82c591d98b5c67f44ff1fc = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storecb2017140d82c591d98b5c67f44ff1fc.url(options),
    method: 'post',
})

storecb2017140d82c591d98b5c67f44ff1fc.definition = {
    methods: ["post"],
    url: '/dashboard/digitalize',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\DigitalizeController::store
* @see app/Http/Controllers/Api/DigitalizeController.php:33
* @route '/dashboard/digitalize'
*/
storecb2017140d82c591d98b5c67f44ff1fc.url = (options?: RouteQueryOptions) => {
    return storecb2017140d82c591d98b5c67f44ff1fc.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\DigitalizeController::store
* @see app/Http/Controllers/Api/DigitalizeController.php:33
* @route '/dashboard/digitalize'
*/
storecb2017140d82c591d98b5c67f44ff1fc.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storecb2017140d82c591d98b5c67f44ff1fc.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::store
* @see app/Http/Controllers/Api/DigitalizeController.php:33
* @route '/dashboard/digitalize'
*/
const storecb2017140d82c591d98b5c67f44ff1fcForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storecb2017140d82c591d98b5c67f44ff1fc.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::store
* @see app/Http/Controllers/Api/DigitalizeController.php:33
* @route '/dashboard/digitalize'
*/
storecb2017140d82c591d98b5c67f44ff1fcForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storecb2017140d82c591d98b5c67f44ff1fc.url(options),
    method: 'post',
})

storecb2017140d82c591d98b5c67f44ff1fc.form = storecb2017140d82c591d98b5c67f44ff1fcForm

export const store = {
    '/api/digitalize': stored864a3a2374b8ccf16b0191fdf968511,
    '/dashboard/digitalize': storecb2017140d82c591d98b5c67f44ff1fc,
}

/**
* @see \App\Http\Controllers\Api\DigitalizeController::index
* @see app/Http/Controllers/Api/DigitalizeController.php:500
* @route '/api/data'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/data',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\DigitalizeController::index
* @see app/Http/Controllers/Api/DigitalizeController.php:500
* @route '/api/data'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\DigitalizeController::index
* @see app/Http/Controllers/Api/DigitalizeController.php:500
* @route '/api/data'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::index
* @see app/Http/Controllers/Api/DigitalizeController.php:500
* @route '/api/data'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::index
* @see app/Http/Controllers/Api/DigitalizeController.php:500
* @route '/api/data'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::index
* @see app/Http/Controllers/Api/DigitalizeController.php:500
* @route '/api/data'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::index
* @see app/Http/Controllers/Api/DigitalizeController.php:500
* @route '/api/data'
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
* @see \App\Http\Controllers\Api\DigitalizeController::show
* @see app/Http/Controllers/Api/DigitalizeController.php:519
* @route '/api/data/{data}'
*/
export const show = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/data/{data}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\DigitalizeController::show
* @see app/Http/Controllers/Api/DigitalizeController.php:519
* @route '/api/data/{data}'
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
* @see \App\Http\Controllers\Api\DigitalizeController::show
* @see app/Http/Controllers/Api/DigitalizeController.php:519
* @route '/api/data/{data}'
*/
show.get = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::show
* @see app/Http/Controllers/Api/DigitalizeController.php:519
* @route '/api/data/{data}'
*/
show.head = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::show
* @see app/Http/Controllers/Api/DigitalizeController.php:519
* @route '/api/data/{data}'
*/
const showForm = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::show
* @see app/Http/Controllers/Api/DigitalizeController.php:519
* @route '/api/data/{data}'
*/
showForm.get = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::show
* @see app/Http/Controllers/Api/DigitalizeController.php:519
* @route '/api/data/{data}'
*/
showForm.head = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\Api\DigitalizeController::digitalizeOptions
* @see app/Http/Controllers/Api/DigitalizeController.php:475
* @route '/dashboard/api/digitalize-options'
*/
export const digitalizeOptions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: digitalizeOptions.url(options),
    method: 'get',
})

digitalizeOptions.definition = {
    methods: ["get","head"],
    url: '/dashboard/api/digitalize-options',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\DigitalizeController::digitalizeOptions
* @see app/Http/Controllers/Api/DigitalizeController.php:475
* @route '/dashboard/api/digitalize-options'
*/
digitalizeOptions.url = (options?: RouteQueryOptions) => {
    return digitalizeOptions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\DigitalizeController::digitalizeOptions
* @see app/Http/Controllers/Api/DigitalizeController.php:475
* @route '/dashboard/api/digitalize-options'
*/
digitalizeOptions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: digitalizeOptions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::digitalizeOptions
* @see app/Http/Controllers/Api/DigitalizeController.php:475
* @route '/dashboard/api/digitalize-options'
*/
digitalizeOptions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: digitalizeOptions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::digitalizeOptions
* @see app/Http/Controllers/Api/DigitalizeController.php:475
* @route '/dashboard/api/digitalize-options'
*/
const digitalizeOptionsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: digitalizeOptions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::digitalizeOptions
* @see app/Http/Controllers/Api/DigitalizeController.php:475
* @route '/dashboard/api/digitalize-options'
*/
digitalizeOptionsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: digitalizeOptions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::digitalizeOptions
* @see app/Http/Controllers/Api/DigitalizeController.php:475
* @route '/dashboard/api/digitalize-options'
*/
digitalizeOptionsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: digitalizeOptions.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

digitalizeOptions.form = digitalizeOptionsForm

/**
* @see \App\Http\Controllers\Api\DigitalizeController::storeBatch
* @see app/Http/Controllers/Api/DigitalizeController.php:169
* @route '/dashboard/digitalize/batch'
*/
export const storeBatch = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeBatch.url(options),
    method: 'post',
})

storeBatch.definition = {
    methods: ["post"],
    url: '/dashboard/digitalize/batch',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\DigitalizeController::storeBatch
* @see app/Http/Controllers/Api/DigitalizeController.php:169
* @route '/dashboard/digitalize/batch'
*/
storeBatch.url = (options?: RouteQueryOptions) => {
    return storeBatch.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\DigitalizeController::storeBatch
* @see app/Http/Controllers/Api/DigitalizeController.php:169
* @route '/dashboard/digitalize/batch'
*/
storeBatch.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeBatch.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::storeBatch
* @see app/Http/Controllers/Api/DigitalizeController.php:169
* @route '/dashboard/digitalize/batch'
*/
const storeBatchForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeBatch.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::storeBatch
* @see app/Http/Controllers/Api/DigitalizeController.php:169
* @route '/dashboard/digitalize/batch'
*/
storeBatchForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeBatch.url(options),
    method: 'post',
})

storeBatch.form = storeBatchForm

/**
* @see \App\Http\Controllers\Api\DigitalizeController::appendToTable
* @see app/Http/Controllers/Api/DigitalizeController.php:269
* @route '/dashboard/api/data/{data}/append-rows'
*/
export const appendToTable = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: appendToTable.url(args, options),
    method: 'post',
})

appendToTable.definition = {
    methods: ["post"],
    url: '/dashboard/api/data/{data}/append-rows',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\DigitalizeController::appendToTable
* @see app/Http/Controllers/Api/DigitalizeController.php:269
* @route '/dashboard/api/data/{data}/append-rows'
*/
appendToTable.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return appendToTable.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\DigitalizeController::appendToTable
* @see app/Http/Controllers/Api/DigitalizeController.php:269
* @route '/dashboard/api/data/{data}/append-rows'
*/
appendToTable.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: appendToTable.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::appendToTable
* @see app/Http/Controllers/Api/DigitalizeController.php:269
* @route '/dashboard/api/data/{data}/append-rows'
*/
const appendToTableForm = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: appendToTable.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::appendToTable
* @see app/Http/Controllers/Api/DigitalizeController.php:269
* @route '/dashboard/api/data/{data}/append-rows'
*/
appendToTableForm.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: appendToTable.url(args, options),
    method: 'post',
})

appendToTable.form = appendToTableForm

/**
* @see \App\Http\Controllers\Api\DigitalizeController::appendToDoc
* @see app/Http/Controllers/Api/DigitalizeController.php:381
* @route '/dashboard/api/data/{data}/append-doc'
*/
export const appendToDoc = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: appendToDoc.url(args, options),
    method: 'post',
})

appendToDoc.definition = {
    methods: ["post"],
    url: '/dashboard/api/data/{data}/append-doc',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\DigitalizeController::appendToDoc
* @see app/Http/Controllers/Api/DigitalizeController.php:381
* @route '/dashboard/api/data/{data}/append-doc'
*/
appendToDoc.url = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return appendToDoc.definition.url
            .replace('{data}', parsedArgs.data.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\DigitalizeController::appendToDoc
* @see app/Http/Controllers/Api/DigitalizeController.php:381
* @route '/dashboard/api/data/{data}/append-doc'
*/
appendToDoc.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: appendToDoc.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::appendToDoc
* @see app/Http/Controllers/Api/DigitalizeController.php:381
* @route '/dashboard/api/data/{data}/append-doc'
*/
const appendToDocForm = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: appendToDoc.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::appendToDoc
* @see app/Http/Controllers/Api/DigitalizeController.php:381
* @route '/dashboard/api/data/{data}/append-doc'
*/
appendToDocForm.post = (args: { data: number | { id: number } } | [data: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: appendToDoc.url(args, options),
    method: 'post',
})

appendToDoc.form = appendToDocForm

const DigitalizeController = { store, index, show, digitalizeOptions, storeBatch, appendToTable, appendToDoc }

export default DigitalizeController