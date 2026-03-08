<?php

namespace App\Ai\Agents;

use App\Ai\Tools\AddTableRowTool;
use App\Ai\Tools\AppendDocContentTool;
use App\Ai\Tools\DeleteTableRowTool;
use App\Ai\Tools\ReplaceDocContentTool;
use App\Ai\Tools\UpdateTableRowTool;
use App\Ai\Tools\WebSearchTool;
use App\Models\Data;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Data insight agent that can edit table data (add/update/delete rows) or document data (replace/append content).
 * Use with prompt() so the gateway can run the tool loop.
 */
class DataInsightAgenticAgent implements Agent, HasTools
{
    use Promptable;

    public function __construct(
        protected Data $data,
    ) {}

    public function instructions(): Stringable|string
    {
        return 'You are a helpful data analyst. The user will provide a block of data (either tabular with headers and rows, or document text) and a question or request. '
            .'Answer using the provided data. Be concise and accurate. '
            .'Users often ask about the document or about people or topics in it; when the answer is not in the uploaded data but is general knowledge online (e.g. an author\'s age, a company\'s revenue, a person\'s biography), you MUST call web_search with a short query and answer from the results. Do not suggest they search elsewhere; use web_search and give the answer. If you got the answer from web_search, state it clearly and do not add a line recommending Wikipedia or Google. '
            .'When the data is a table, you have tools to add, update, or delete rows. Use them when the user asks to add a row, insert data, change a row, update a cell, remove a row, or delete a row. '
            .'If the user says to fill the table, populate it, or add sample data, infer plausible rows from the column headers and add them with add_table_row (one call per row). '
            .'Row indices are 0-based (first data row is index 0). When adding or updating, provide cells in the same order as the table headers. '
            .'When the data is a document, you have tools to replace or append content. Use replace_doc_content when the user asks to replace, rewrite, or set the document (or one page). Use append_doc_content when they ask to add or append text at the end. '
            .'For multi-page documents, page numbers are 1-based. '
            .'After using a tool, confirm briefly what you did. Respond in markdown when appropriate.';
    }

    public function tools(): iterable
    {
        $digital = $this->data->digital_data;
        if (! is_array($digital)) {
            return [new WebSearchTool];
        }

        $type = $digital['type'] ?? '';
        $tools = [new WebSearchTool];

        if ($type === 'table') {
            $tools[] = new AddTableRowTool($this->data);
            $tools[] = new UpdateTableRowTool($this->data);
            $tools[] = new DeleteTableRowTool($this->data);
        }

        if ($type === 'doc') {
            $tools[] = new ReplaceDocContentTool($this->data);
            $tools[] = new AppendDocContentTool($this->data);
        }

        return $tools;
    }
}
