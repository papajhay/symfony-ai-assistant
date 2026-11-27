<?php

namespace App\Agent;

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\AI\Store\RetrieverInterface;
use Throwable;

#[AsTool(
    'similarity_search',
    description: 'Searches indexed Symfony and Symfony AI documentation for relevant passages.',
)]
final readonly class SafeSimilaritySearch
{
    public function __construct(private RetrieverInterface $retriever)
    {
    }

    public function __invoke(string $searchTerm): string
    {
        $query = trim($searchTerm);
        if (strlen($query) < 3 || 1 !== preg_match('/[\p{L}\p{N}]/u', $query)
            || 1 === preg_match('/^(?:query|search term|placeholder|your query|test|foo|bar|n\/?a|none|null|undefined)$/i', $query)
        ) {
            return 'Documentation search was skipped because the search query was empty or not meaningful. Answer from general knowledge and clearly state that the answer was not verified against indexed project documentation.';
        }

        try {
            $results = $this->retriever->retrieve($query);
            $documents = is_array($results) ? $results : iterator_to_array($results);
        } catch (Throwable) {
            return 'Indexed documentation search is temporarily unavailable. Answer the user directly from general technical knowledge and clearly state that the answer is not based on retrieved project documentation.';
        }

        if ([] === $documents) {
            return 'No relevant indexed documentation was found. Answer the user directly from general technical knowledge and clearly state that the answer is not based on retrieved project documentation.';
        }

        $passages = [];
        foreach ($documents as $document) {
            $metadata = $document->getMetadata();
            $title = $metadata->getTitle() ?? 'Untitled document';
            $source = $metadata->getSource();
            $text = $metadata->getText() ?? $metadata->getSummary();

            $passage = 'Document: '.$title;
            if (null !== $source && '' !== $source) {
                $passage .= "\nSource: ".$source;
            }
            if (null !== $text && '' !== trim($text)) {
                $passage .= "\nExcerpt: ".trim($text);
            }

            $passages[] = $passage;
        }

        return "Relevant indexed documentation:\n\n".implode("\n\n", $passages);
    }
}
