<?php
function newsdesk_render_html_template(string $templatePath, array $values = [], array $htmlFragments = []): void
{
    $template = file_get_contents($templatePath);
    if ($template === false) {
        throw new RuntimeException('Unable to read HTML template: ' . $templatePath);
    }

    $assetBaseUrl = defined('NEWS_DESK_ASSET_BASE_URL') ? NEWS_DESK_ASSET_BASE_URL : '.';
    $values['ASSET_BASE_URL'] = rtrim((string)$assetBaseUrl, '/') ?: '/';

    $replacements = [];
    foreach ($values as $name => $value) {
        $replacements['{{' . $name . '}}'] = htmlspecialchars(
            (string)$value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
    foreach ($htmlFragments as $name => $fragment) {
        $replacements['{{' . $name . '}}'] = $fragment;
    }

    $output = strtr($template, $replacements);
    if (preg_match('/\{\{[A-Z_]+\}\}/', $output, $unresolved)) {
        throw new RuntimeException('Unresolved HTML template placeholder: ' . $unresolved[0]);
    }

    echo $output;
}
