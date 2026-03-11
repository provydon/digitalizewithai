import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
import data from './data'
/**
* @see \App\Http\Controllers\Api\DigitalizeController::digitalizeOptions
* @see app/Http/Controllers/Api/DigitalizeController.php:487
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
* @see app/Http/Controllers/Api/DigitalizeController.php:487
* @route '/dashboard/api/digitalize-options'
*/
digitalizeOptions.url = (options?: RouteQueryOptions) => {
    return digitalizeOptions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\DigitalizeController::digitalizeOptions
* @see app/Http/Controllers/Api/DigitalizeController.php:487
* @route '/dashboard/api/digitalize-options'
*/
digitalizeOptions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: digitalizeOptions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\DigitalizeController::digitalizeOptions
* @see app/Http/Controllers/Api/DigitalizeController.php:487
* @route '/dashboard/api/digitalize-options'
*/
digitalizeOptions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: digitalizeOptions.url(options),
    method: 'head',
})

const api = {
    digitalizeOptions: Object.assign(digitalizeOptions, digitalizeOptions),
    data: Object.assign(data, data),
}

export default api