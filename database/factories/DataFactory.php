<?php

namespace Database\Factories;

use App\Models\Data;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Data>
 */
class DataFactory extends Factory
{
    protected $model = Data::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'status' => 'ready',
            'raw_data' => ['disk' => 'local', 'path' => 'digitalize/test.txt', 'mime_type' => 'text/plain'],
            'digital_data' => [
                'type' => 'doc',
                'content' => fake()->paragraph(),
                'doc_page_count' => 1,
                'doc_pages' => [fake()->paragraph()],
                'suggested_prompts' => [],
                'insights' => [],
            ],
            'ai_provider' => null,
            'ai_model' => null,
            'extraction_started_at' => null,
            'extraction_duration_seconds' => null,
            'extraction_failure_message' => null,
        ];
    }

    public function table(array $headers = ['Col A', 'Col B'], array $rows = []): static
    {
        $defaultRows = $rows ?: [['a1', 'b1'], ['a2', 'b2']];
        $content = json_encode(['headers' => $headers, 'rows' => $defaultRows]);

        return $this->state(fn (array $attributes) => [
            'digital_data' => array_merge($attributes['digital_data'] ?? [], [
                'type' => 'table',
                'content' => $content,
                'table_row_count' => count($defaultRows),
                'suggested_prompts' => [],
                'insights' => [],
            ]),
        ]);
    }

    public function doc(int $pageCount = 1): static
    {
        $pages = array_fill(0, $pageCount, fake()->paragraph());
        $content = implode("\n\n", $pages);

        return $this->state(fn (array $attributes) => [
            'digital_data' => array_merge($attributes['digital_data'] ?? [], [
                'type' => 'doc',
                'content' => $content,
                'doc_page_count' => $pageCount,
                'doc_pages' => $pages,
                'suggested_prompts' => [],
                'insights' => [],
            ]),
        ]);
    }
}
