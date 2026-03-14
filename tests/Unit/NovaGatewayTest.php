<?php

use App\Ai\Gateway\NovaGateway;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;

test('nova structured output prompt uses chart schema keys', function () {
    $gateway = new class(new PendingRequest(new Factory), 'test-key') extends NovaGateway
    {
        public function promptFor(array $schema): string
        {
            return $this->structuredOutputSchemaPrompt($schema);
        }
    };

    $prompt = $gateway->promptFor([
        'chartType' => 'string',
        'labelColumn' => 'integer',
        'valueColumn' => 'integer',
        'aggregation' => 'string',
        'xAxisName' => 'string',
        'yAxisName' => 'string',
        'title' => 'string',
    ]);

    expect($prompt)
        ->toContain('"chartType"', '"labelColumn"', '"valueColumn"', '"aggregation"', '"xAxisName"', '"yAxisName"', '"title"')
        ->toContain('exactly one of: "bar", "line", or "pie"')
        ->toContain('0-based integer column indexes')
        ->toContain('exactly one of: "none", "sum", or "count"')
        ->not->toContain('"type" (must be the string "doc" or "table")');
});

test('nova unwrap structured content accepts fenced chart json', function () {
    $gateway = new class(new PendingRequest(new Factory), 'test-key') extends NovaGateway
    {
        public function unwrap(string $content): ?array
        {
            return $this->unwrapStructuredContent($content);
        }
    };

    $decoded = $gateway->unwrap(<<<'TEXT'
```json
{"chartType":"bar","labelColumn":0,"valueColumn":1,"aggregation":"sum","xAxisName":"Region","yAxisName":"Sales","title":"Sales by region"}
```
TEXT);

    expect($decoded)->toBe([
        'chartType' => 'bar',
        'labelColumn' => 0,
        'valueColumn' => 1,
        'aggregation' => 'sum',
        'xAxisName' => 'Region',
        'yAxisName' => 'Sales',
        'title' => 'Sales by region',
    ]);
});
