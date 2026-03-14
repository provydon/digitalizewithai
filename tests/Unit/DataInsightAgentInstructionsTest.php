<?php

use App\Ai\Agents\DataInsightAgent;
use App\Ai\Agents\DataInsightAgenticAgent;
use App\Ai\Agents\DataInsightStreamingAgent;
use App\Models\Data;

test('data insight agent instructions require final answer only', function () {
    $instructions = (string) (new DataInsightAgent)->instructions();

    expect($instructions)
        ->toContain('Return only the final answer for the user.')
        ->toContain('Do not reveal chain-of-thought')
        ->toContain('Do not include "thinking", "thought process", "reasoning"');
});

test('data insight streaming agent instructions require final answer only', function () {
    $instructions = (string) (new DataInsightStreamingAgent)->instructions();

    expect($instructions)
        ->toContain('Return only the final answer for the user.')
        ->toContain('Do not reveal chain-of-thought')
        ->toContain('Do not include "thinking", "thought process", "reasoning"');
});

test('data insight agentic agent instructions require final answer only', function () {
    $instructions = (string) (new DataInsightAgenticAgent(new Data))->instructions();

    expect($instructions)
        ->toContain('Return only the final answer for the user.')
        ->toContain('Do not reveal chain-of-thought')
        ->toContain('Do not include "thinking", "thought process", "reasoning"');
});
