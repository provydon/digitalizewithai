import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\DigitalizeController::batch
* @see app/Http/Controllers/Api/DigitalizeController.php:179
* @route '/dashboard/digitalize/batch'
*/
export const batch = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: batch.url(options),
    method: 'post',
})

batch.definition = {
    methods: ["post"],
    url: '/dashboard/digitalize/batch',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\DigitalizeController::batch
* @see app/Http/Controllers/Api/DigitalizeController.php:179
* @route '/dashboard/digitalize/batch'
*/
batch.url = (options?: RouteQueryOptions) => {
    return batch.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\DigitalizeController::batch
* @see app/Http/Controllers/Api/DigitalizeController.php:179
* @route '/dashboard/digitalize/batch'
*/
batch.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: batch.url(options),
    method: 'post',
})

const digitalize = {
    batch: Object.assign(batch, batch),
}

export default digitalize