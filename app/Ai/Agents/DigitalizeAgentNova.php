<?php

namespace App\Ai\Agents;

use Stringable;

/**
 * Nova-specific digitalize agent with stricter table-detection rules.
 * Extends the main DigitalizeAgent; Nova often returns "doc" for list-like
 * content (e.g. state + abbreviation), so we add rules that force "table"
 * when content has repeated rows with columns.
 */
class DigitalizeAgentNova extends DigitalizeAgent
{
    public function instructions(): Stringable|string
    {
        $base = (string) parent::instructions();

        $novaTableRules = ' ADDITIONAL RULES (you MUST follow these): '
            .'ACCURACY: Strive for accuracy. Do not guess, infer, or assume—work only with what you can clearly see in the image. If something is unclear or partially visible, transcribe what is visible; do not invent or fill in missing content. '
            .'COMPLETENESS: Extract all data from the image. Do not summarize or skip content. For type "doc", include every line and paragraph. For type "table", extract every row and every cell/field value: include all headers and all data rows; do not omit columns or rows. If the image shows a long list or many rows, include every one. '
            .'CLASSIFICATION: Use type "table" whenever the content has REPEATED ROWS with the SAME COLUMNS. Examples that MUST be type "table": (1) Lists of items with two or more columns: state name + abbreviation (e.g. Alabama AL), name + value, key + value, product + price. (2) Any list where each line has the same structure: "Item1\tValue1", "Item2\tValue2", etc. (3) Balance sheets, income statements, spreadsheets, price lists, rosters, directories. Do NOT use type "doc" for lists with columns—use "doc" only for continuous prose, paragraphs, or content that is NOT a repeated row-column structure.';

        return $base.$novaTableRules;
    }
}
