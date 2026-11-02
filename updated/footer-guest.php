<?php
require_once __DIR__ . '/template-renderer.php';
newsdesk_render_html_template(
    __DIR__ . '/templates/footer-guest.html',
    ['CURRENT_YEAR' => date('Y')]
);
