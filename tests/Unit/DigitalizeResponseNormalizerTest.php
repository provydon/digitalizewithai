<?php

use App\Support\DigitalizeResponseNormalizer;

test('digitalize response normalizer unwraps nested doc payloads and pages', function () {
    $response = [
        'type' => 'doc',
        'content' => <<<'TEXT'
```json
{"type":"doc","content":"Page one text","doc_page_count":2,"doc_pages":["Page one text","Page two text"]}
```
TEXT,
        'doc_page_count' => 2,
        'doc_pages' => [
            <<<'TEXT'
```json
{"type":"doc","content":"Page one text","doc_page_count":2,"doc_pages":["Page one text","Page two text"]}
```
TEXT,
            'Page two text',
        ],
        'suggested_prompts' => [],
        'insights' => [],
    ];

    $normalized = DigitalizeResponseNormalizer::normalize($response);

    expect($normalized['type'])->toBe('doc')
        ->and($normalized['content'])->toBe("Page one text\n\nPage two text")
        ->and($normalized['doc_page_count'])->toBe(2)
        ->and($normalized['doc_pages'])->toBe(['Page one text', 'Page two text']);
});
