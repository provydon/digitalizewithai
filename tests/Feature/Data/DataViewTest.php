<?php

use App\Ai\Agents\ChartSuggestionAgent;
use App\Models\Data;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;

beforeEach(function () {
    Storage::fake('local');
});

test('data show page requires authentication', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);

    $response = $this->get(route('dashboard.data.show', $data));
    $response->assertRedirect(route('login'));
});

test('authenticated user can view own data show page', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->get(route('dashboard.data.show', $data));

    $response->assertOk();
});

test('data show page returns 404 for other user data', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user);
    $response = $this->get(route('dashboard.data.show', $data));

    $response->assertNotFound();
});

test('dashboard api data show returns json for own data', function () {
    $user = User::factory()->create();
    $data = Data::factory()->doc(1)->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.show', $data));

    $response->assertOk()
        ->assertJsonPath('id', $data->id)
        ->assertJsonPath('name', $data->name)
        ->assertJsonStructure(['digital_data', 'raw_data']);
});

test('dashboard api data show returns 404 for other user data', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.show', $data));

    $response->assertNotFound();
});

test('dashboard api data update name', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);

    $this->actingAs($user);
    $response = $this->patchJson(route('dashboard.api.data.update', $data), [
        'name' => 'New Name',
    ]);

    $response->assertOk()->assertJsonPath('name', 'New Name');
    $data->refresh();
    expect($data->name)->toBe('New Name');
});

test('dashboard api data update name rejects empty', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->patchJson(route('dashboard.api.data.update', $data), [
        'name' => '   ',
    ]);

    $response->assertStatus(422);
});

test('dashboard api doc page returns page content for doc data', function () {
    $user = User::factory()->create();
    $data = Data::factory()->doc(2)->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.doc-page', ['data' => $data->id, 'page' => 1]));

    $response->assertOk()
        ->assertJsonStructure(['page', 'total_pages', 'content'])
        ->assertJsonPath('page', 1)
        ->assertJsonPath('total_pages', 2);
});

test('dashboard api doc content returns full content for doc data', function () {
    $user = User::factory()->create();
    $data = Data::factory()->doc(1)->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.doc-content', $data));

    $response->assertOk()->assertJsonStructure(['content']);
});

test('dashboard api doc content returns 404 for table data', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table()->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->getJson(route('dashboard.api.data.doc-content', $data));

    $response->assertNotFound();
});

test('dashboard api update doc content updates doc', function () {
    $user = User::factory()->create();
    $data = Data::factory()->doc(1)->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->patchJson(route('dashboard.api.data.doc-content.update', $data), [
        'content' => 'Updated paragraph text.',
    ]);

    $response->assertOk()->assertJsonPath('content', 'Updated paragraph text.');
    $data->refresh();
    expect($data->digital_data['content'])->toContain('Updated paragraph text.');
});

test('dashboard api ask requires question', function () {
    $user = User::factory()->create();
    $data = Data::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.ask', $data), []);

    $response->assertStatus(422);
});

test('dashboard api chart suggestion requires table data', function () {
    $user = User::factory()->create();
    $data = Data::factory()->doc(1)->create(['user_id' => $user->id]);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.chart-suggestion', $data));

    $response->assertStatus(422)
        ->assertJsonFragment(['message' => 'Table data is required for chart suggestion.']);
});

test('dashboard api chart suggestion returns generated chart config', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table(
        ['Month', 'Revenue'],
        [['Jan', '120'], ['Feb', '140']],
    )->create(['user_id' => $user->id]);

    $agent = Mockery::mock(ChartSuggestionAgent::class);
    $agent->shouldReceive('prompt')
        ->once()
        ->withArgs(function (string $prompt, array $attachments = [], ?string $provider = null, ?string $model = null): bool {
            expect($prompt)
                ->toContain('0: Month, 1: Revenue')
                ->toContain('Jan | 120')
                ->toContain('Suggest the best chart type, which column indices to use for labels and values, whether aggregation is needed, and clear axis names for the chart.')
                ->toContain('Recommended business/logistics chart candidates based on the data shape');

            return $attachments === [] && $provider === null && $model === null;
        })
        ->andReturn(new AgentResponse(
            'test-chart-1',
            json_encode([
                'chartType' => 'line',
                'labelColumn' => 0,
                'valueColumn' => 1,
                'aggregation' => 'none',
                'xAxisName' => 'Month',
                'yAxisName' => 'Revenue',
                'title' => 'Revenue over time',
            ]),
            new Usage,
            new Meta,
        ));
    $this->app->instance(ChartSuggestionAgent::class, $agent);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.chart-suggestion', $data));

    $response->assertOk()
        ->assertJsonPath('chartType', 'line')
        ->assertJsonPath('labelColumn', 0)
        ->assertJsonPath('valueColumn', 1)
        ->assertJsonPath('aggregation', 'none')
        ->assertJsonPath('xAxisName', 'Month')
        ->assertJsonPath('yAxisName', 'Revenue')
        ->assertJsonPath('title', 'Revenue over time');
});

test('dashboard api chart suggestion uses nova provider and normalizes columns', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table(
        ['Region', 'Sales'],
        [['North', '40'], ['South', '60']],
    )->create([
        'user_id' => $user->id,
        'ai_provider' => 'nova',
        'ai_model' => 'nova-pro-v1',
    ]);

    $agent = Mockery::mock(ChartSuggestionAgent::class);
    $agent->shouldReceive('prompt')
        ->once()
        ->withArgs(function (string $prompt, array $attachments = [], ?string $provider = null, ?string $model = null): bool {
            expect($prompt)
                ->toContain('0: Region, 1: Sales')
                ->toContain('User wants this specific chart: "pie chart of sales by region"');

            return $attachments === [] && $provider === 'nova' && $model === 'nova-pro-v1';
        })
        ->andReturn(new AgentResponse(
            'test-chart-2',
            json_encode([
                'chartType' => 'pie',
                'labelColumn' => 0,
                'valueColumn' => 0,
                'aggregation' => 'sum',
                'xAxisName' => '',
                'yAxisName' => 'Sales',
                'title' => 'Sales by region',
            ]),
            new Usage,
            new Meta('nova', 'nova-pro-v1'),
        ));
    $this->app->instance(ChartSuggestionAgent::class, $agent);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.chart-suggestion', $data), [
        'request' => 'pie chart of sales by region',
    ]);

    $response->assertOk()
        ->assertJsonPath('chartType', 'pie')
        ->assertJsonPath('labelColumn', 0)
        ->assertJsonPath('valueColumn', 1)
        ->assertJsonPath('aggregation', 'sum')
        ->assertJsonPath('xAxisName', 'Region')
        ->assertJsonPath('yAxisName', 'Sales')
        ->assertJsonPath('title', 'Sales by region');
});

test('dashboard api chart suggestion parses fenced json responses', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table(
        ['Customer Details', 'ORDERS 6-3-2026', 'Total'],
        [['Customer', 'Chicken Taco x2', '14,150'], ['Glovo', 'Beef Burrito x1', '52,139']],
    )->create([
        'user_id' => $user->id,
        'ai_provider' => 'nova',
        'ai_model' => 'nova-2-lite-v1',
    ]);

    $agent = Mockery::mock(ChartSuggestionAgent::class);
    $agent->shouldReceive('prompt')
        ->once()
        ->andReturn(new AgentResponse(
            'test-chart-fenced',
            <<<'TEXT'
```json
{"chartType":"bar","labelColumn":0,"valueColumn":2,"aggregation":"sum","xAxisName":"Customer Details","yAxisName":"Total","title":"Total by Customer Details"}
```
TEXT,
            new Usage,
            new Meta('nova', 'nova-2-lite-v1'),
        ));
    $this->app->instance(ChartSuggestionAgent::class, $agent);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.chart-suggestion', $data), [
        'request' => 'bar chart of total by customer details',
    ]);

    $response->assertOk()
        ->assertJsonPath('chartType', 'bar')
        ->assertJsonPath('labelColumn', 0)
        ->assertJsonPath('valueColumn', 2)
        ->assertJsonPath('aggregation', 'sum')
        ->assertJsonPath('xAxisName', 'Customer Details')
        ->assertJsonPath('yAxisName', 'Total')
        ->assertJsonPath('title', 'Total by Customer Details');
});

test('dashboard api chart suggestion falls back to heuristic grouped chart for customer spend request', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table(
        ['Date', 'Customer Details', 'Unit Price', 'Total'],
        [['2026-03-05', 'Alice', '13,150', '14,150'], ['2026-03-05', 'Bob', '7,525', '8,025']],
    )->create(['user_id' => $user->id]);

    $agent = Mockery::mock(ChartSuggestionAgent::class);
    $agent->shouldReceive('prompt')
        ->once()
        ->andReturn(new AgentResponse(
            'test-chart-fallback',
            'Sorry, I cannot determine that.',
            new Usage,
            new Meta,
        ));
    $this->app->instance(ChartSuggestionAgent::class, $agent);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.chart-suggestion', $data), [
        'request' => 'money spent per customer',
    ]);

    $response->assertOk()
        ->assertJsonPath('chartType', 'bar')
        ->assertJsonPath('labelColumn', 1)
        ->assertJsonPath('valueColumn', 3)
        ->assertJsonPath('aggregation', 'sum')
        ->assertJsonPath('xAxisName', 'Customer Details')
        ->assertJsonPath('yAxisName', 'Money spent')
        ->assertJsonPath('title', 'Money Spent Per Customer');
});

test('dashboard api chart suggestion falls back to item spend for receipt style tables', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table(
        ['Sl#', 'Customer Details', 'ORDERS 6-3-2026', 'Unit', 'POS', 'T-F', 'Total'],
        [
            ['1', 'Customer', 'Chicken Taco x2', '13,150', 'GTB', '', ''],
            ['', '', 'Takeout x2', '1,000', '', '', ''],
            ['2', 'Glovo', 'Beef Burrito x1', '16,125', '', '', ''],
            ['', '', 'Caramel milkshakes x1', '8,063', '', '', '49,372'],
            ['', '', 'TOTAL= 178,865', '', '', '', ''],
        ],
    )->create(['user_id' => $user->id]);

    $agent = Mockery::mock(ChartSuggestionAgent::class);
    $agent->shouldReceive('prompt')
        ->once()
        ->andReturn(new AgentResponse(
            'test-chart-item-fallback',
            'Not enough information.',
            new Usage,
            new Meta,
        ));
    $this->app->instance(ChartSuggestionAgent::class, $agent);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.chart-suggestion', $data), [
        'request' => 'total money spent per item',
    ]);

    $response->assertOk()
        ->assertJsonPath('chartType', 'bar')
        ->assertJsonPath('labelColumn', 2)
        ->assertJsonPath('valueColumn', 3)
        ->assertJsonPath('aggregation', 'sum')
        ->assertJsonPath('xAxisName', 'ORDERS 6-3-2026')
        ->assertJsonPath('yAxisName', 'Money spent')
        ->assertJsonPath('title', 'Total Money Spent Per Item');
});

test('dashboard api chart suggestion uses shape-based default when ai response is unusable without a custom request', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table(
        ['Date', 'Total'],
        [['Jan', '120'], ['Feb', '140']],
    )->create(['user_id' => $user->id]);

    $agent = Mockery::mock(ChartSuggestionAgent::class);
    $agent->shouldReceive('prompt')
        ->once()
        ->andReturn(new AgentResponse(
            'test-chart-invalid',
            'Not enough information.',
            new Usage,
            new Meta,
        ));
    $this->app->instance(ChartSuggestionAgent::class, $agent);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.chart-suggestion', $data));

    $response->assertOk()
        ->assertJsonPath('chartType', 'line')
        ->assertJsonPath('labelColumn', 0)
        ->assertJsonPath('valueColumn', 1)
        ->assertJsonPath('aggregation', 'sum')
        ->assertJsonPath('xAxisName', 'Date')
        ->assertJsonPath('yAxisName', 'Total')
        ->assertJsonPath('title', 'Total by Date');
});

test('dashboard api chart suggestion avoids low-signal identifier charts without a custom request', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table(
        ['ID', 'Item', 'Total'],
        [['1', 'Widget A', '120'], ['2', 'Widget B', '140']],
    )->create(['user_id' => $user->id]);

    $agent = Mockery::mock(ChartSuggestionAgent::class);
    $agent->shouldReceive('prompt')
        ->once()
        ->andReturn(new AgentResponse(
            'test-chart-low-signal',
            json_encode([
                'chartType' => 'bar',
                'labelColumn' => 0,
                'valueColumn' => 2,
                'aggregation' => 'sum',
                'xAxisName' => 'ID',
                'yAxisName' => 'Total',
                'title' => 'Total by ID',
            ]),
            new Usage,
            new Meta,
        ));
    $this->app->instance(ChartSuggestionAgent::class, $agent);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.chart-suggestion', $data));

    $response->assertOk()
        ->assertJsonPath('chartType', 'bar')
        ->assertJsonPath('labelColumn', 1)
        ->assertJsonPath('valueColumn', 2)
        ->assertJsonPath('aggregation', 'sum')
        ->assertJsonPath('xAxisName', 'Item')
        ->assertJsonPath('yAxisName', 'Total')
        ->assertJsonPath('title', 'Total by Item');
});

test('dashboard api chart suggestion returns a different default chart when prior charts are excluded', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table(
        ['Date', 'Customer Details', 'Total'],
        [['2026-03-01', 'Alice', '120'], ['2026-03-02', 'Bob', '140']],
    )->create(['user_id' => $user->id]);

    $agent = Mockery::mock(ChartSuggestionAgent::class);
    $agent->shouldReceive('prompt')
        ->once()
        ->andReturn(new AgentResponse(
            'test-chart-another',
            'Not enough information.',
            new Usage,
            new Meta,
        ));
    $this->app->instance(ChartSuggestionAgent::class, $agent);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.chart-suggestion', $data), [
        'excludeCharts' => [[
            'chartType' => 'line',
            'labelColumn' => 0,
            'valueColumn' => 2,
            'aggregation' => 'sum',
            'xAxisName' => 'Date',
            'yAxisName' => 'Total',
            'title' => 'Total by Date',
        ]],
    ]);

    $response->assertOk()
        ->assertJsonPath('chartType', 'bar')
        ->assertJsonPath('labelColumn', 1)
        ->assertJsonPath('valueColumn', 2)
        ->assertJsonPath('aggregation', 'sum')
        ->assertJsonPath('xAxisName', 'Customer Details')
        ->assertJsonPath('yAxisName', 'Total')
        ->assertJsonPath('title', 'Total by Customer Details');
});

test('dashboard api chart suggestion uses normalized receipt rows for default shape chart', function () {
    $user = User::factory()->create();
    $data = Data::factory()->table(
        ['Sl#', 'Customer Details', 'ORDERS 6-3-2026', 'Unit', 'POS', 'T-F', 'Total'],
        [
            ['1', 'Customer', 'Chicken Taco x2', '13,150', 'GTB', '', ''],
            ['', '', 'Takeout x2', '1,000', '', '', ''],
            ['2', 'Glovo', 'Beef Burrito x1', '16,125', '', '', ''],
            ['', '', 'Caramel milkshakes x1', '8,063', '', '', '49,372'],
            ['', '', 'TOTAL= 178,865', '', '', '', ''],
        ],
    )->create(['user_id' => $user->id]);

    $agent = Mockery::mock(ChartSuggestionAgent::class);
    $agent->shouldReceive('prompt')
        ->once()
        ->andReturn(new AgentResponse(
            'test-chart-receipt-default',
            'Unable to decide.',
            new Usage,
            new Meta,
        ));
    $this->app->instance(ChartSuggestionAgent::class, $agent);

    $this->actingAs($user);
    $response = $this->postJson(route('dashboard.api.data.chart-suggestion', $data));

    $response->assertOk()
        ->assertJsonPath('chartType', 'bar')
        ->assertJsonPath('labelColumn', 1)
        ->assertJsonPath('valueColumn', 3)
        ->assertJsonPath('aggregation', 'sum')
        ->assertJsonPath('xAxisName', 'Customer Details')
        ->assertJsonPath('yAxisName', 'Unit')
        ->assertJsonPath('title', 'Unit by Customer Details');
});
