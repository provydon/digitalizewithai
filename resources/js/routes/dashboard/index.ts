import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
import api from './api'
import digitalize8239d2 from './digitalize'
import data from './data'
/**
* @see \App\Http\Controllers\Api\DigitalizeController::digitalize
* @see app/Http/Controllers/Api/DigitalizeController.php:33
* @route '/dashboard/digitalize'
*/
export const digitalize = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: digitalize.url(options),
    method: 'post',
})

digitalize.definition = {
    methods: ["post"],
    url: '/dashboard/digitalize',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\DigitalizeController::digitalize
* @see app/Http/Controllers/Api/DigitalizeController.php:33
* @route '/dashboard/digitalize'
*/
digitalize.url = (options?: RouteQueryOptions) => {
    return digitalize.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\DigitalizeController::digitalize
* @see app/Http/Controllers/Api/DigitalizeController.php:33
* @route '/dashboard/digitalize'
*/
digitalize.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: digitalize.url(options),
    method: 'post',
})

const dashboard = {
    api: Object.assign(api, api),
    digitalize: Object.assign(digitalize, digitalize8239d2),
    data: Object.assign(data, data),
}

export default dashboard