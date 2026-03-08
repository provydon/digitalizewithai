import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
import data from './data'
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

const api = {
    digitalizeOptions: Object.assign(digitalizeOptions, digitalizeOptions),
    data: Object.assign(data, data),
}

export default api