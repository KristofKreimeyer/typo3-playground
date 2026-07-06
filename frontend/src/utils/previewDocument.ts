import type { RenderPreviewResponse } from '../types';

function escapeAttribute(value: string): string {
  return value.replaceAll('&', '&amp;').replaceAll('"', '&quot;').replaceAll('<', '&lt;');
}

export function buildPreviewDocument(preview: RenderPreviewResponse): string {
  return [
    '<!doctype html>',
    '<html lang="en">',
    '<head>',
    '<meta charset="utf-8">',
    '<meta name="viewport" content="width=device-width, initial-scale=1">',
    `<link rel="stylesheet" href="${escapeAttribute(preview.css)}">`,
    '</head>',
    `<body>${preview.html}</body>`,
    '</html>',
  ].join('');
}
