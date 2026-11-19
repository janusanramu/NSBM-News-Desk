<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once __DIR__.'/../NSBM-News-Desk-feature-login-register-page/includes/bootstrap.php';

function article_text(DOMNode $node): string
{
    $text = preg_replace('/\s+/u', ' ', trim((string)$node->textContent));
    if ($text === null) {
        throw new RuntimeException('Could not normalize article text.');
    }

    return $text;
}

function source_article(DOMXPath $xpath, string $sectionId, string $category): array
{
    $sections = $xpath->query('//*[@id="'.$sectionId.'"]');
    $section = $sections ? $sections->item(0) : null;
    if (!$section) {
        throw new RuntimeException('Missing source article section: '.$sectionId);
    }

    $bodies = $xpath->query(
        './/article[contains(concat(" ", normalize-space(@class), " "), " article-body ")]',
        $section
    );
    $body = $bodies ? $bodies->item(0) : null;
    if (!$body) {
        throw new RuntimeException('Missing article body: '.$sectionId);
    }

    $lead = '';
    $blocks = [];
    foreach ($body->childNodes as $child) {
        if (!$child instanceof DOMElement) {
            continue;
        }

        $tag = strtolower($child->tagName);
        $classes = preg_split('/\s+/', trim($child->getAttribute('class'))) ?: [];
        if ($tag === 'p' && in_array('lead', $classes, true)) {
            $lead = article_text($child);
            continue;
        }

        if ($tag === 'p' || $tag === 'h2' || $tag === 'h3') {
            $text = article_text($child);
            if ($text !== '') {
                $blocks[] = in_array($tag, ['h2', 'h3'], true) ? '## '.$text : $text;
            }
            continue;
        }

        if ($tag === 'blockquote') {
            $quoteParts = [];
            foreach ($child->childNodes as $quoteChild) {
                if ($quoteChild instanceof DOMElement
                    && in_array(strtolower($quoteChild->tagName), ['p', 'cite'], true)) {
                    $text = article_text($quoteChild);
                    if ($text !== '') {
                        $quoteParts[] = $text;
                    }
                }
            }
            if ($quoteParts) {
                $blocks[] = implode("\n", $quoteParts);
            }
            continue;
        }

        if ($tag === 'figure') {
            $captions = $xpath->query('.//figcaption', $child);
            $caption = $captions ? $captions->item(0) : null;
            if ($caption) {
                $text = article_text($caption);
                if ($text !== '') {
                    $blocks[] = $text;
                }
            }
            continue;
        }

        if ($tag === 'ul' || $tag === 'ol') {
            $items = $xpath->query('.//li', $child);
            if ($items) {
                foreach ($items as $item) {
                    $text = article_text($item);
                    if ($text !== '') {
                        $blocks[] = '- '.$text;
                    }
                }
            }
        }
    }

    $cards = $xpath->query(
        '//article[contains(concat(" ", normalize-space(@class), " "), " news-card ") and translate(@data-category, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")="'.$category.'"]'
    );
    $card = $cards ? $cards->item(0) : null;
    $summaries = $card ? $xpath->query(
        './/p[contains(concat(" ", normalize-space(@class), " "), " description ")]',
        $card
    ) : false;
    $summary = $summaries ? $summaries->item(0) : null;

    if ($lead === '' || !$blocks || !$summary) {
        throw new RuntimeException('Source article is incomplete: '.$sectionId);
    }

    return [
        'summary' => article_text($summary),
        'body' => implode("\n\n", array_merge([$lead], $blocks)),
        'partial_body' => implode("\n\n", $blocks),
        'legacy_body' => implode("\n\n", array_merge(
            [$lead],
            array_map(static fn(string $block): string => preg_replace('/^## /', '', $block) ?? $block, $blocks)
        )),
    ];
}

try {
    $sourcePath = __DIR__.'/../news/before_login_news.html';
    $html = file_get_contents($sourcePath);
    if ($html === false) {
        throw new RuntimeException('Could not read the original news articles.');
    }

    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML('<?xml encoding="UTF-8">'.$html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if (!$loaded) {
        throw new RuntimeException('Could not parse the original news articles.');
    }

    $xpath = new DOMXPath($document);
    $database = database();
    $update = $database->prepare(
        'UPDATE articles SET summary=?,body=? WHERE slug=? AND (body=summary OR body=? OR body=?)'
    );

    $sections = [
        1 => ['computing', 'computing'],
        2 => ['business', 'business'],
        3 => ['engineering', 'engineering'],
        4 => ['events', 'events'],
        5 => ['sports', 'sports'],
        6 => ['notices', 'notices'],
    ];

    foreach ($sections as $number => [$sectionId, $category]) {
        $content = source_article($xpath, $sectionId, $category);

        $update->execute([
            $content['summary'],
            $content['body'],
            'nsbm-original-story-'.$number,
            $content['partial_body'],
            $content['legacy_body'],
        ]);
        echo $update->rowCount()
            ? 'Restored full article: '.$sectionId.PHP_EOL
            : 'Already restored or not a seeded article: '.$sectionId.PHP_EOL;
    }
} catch (Throwable $error) {
    fwrite(STDERR, 'Could not restore articles: '.$error->getMessage().PHP_EOL);
    exit(1);
}
