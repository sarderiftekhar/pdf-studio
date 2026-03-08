<?php

use PdfStudio\Laravel\Thumbnail\ThumbnailResult;

it('stores thumbnail content and metadata', function () {
    $result = new ThumbnailResult(
        content: 'fake-image-bytes',
        mimeType: 'image/png',
        width: 300,
        height: 400,
    );

    expect($result->content())->toBe('fake-image-bytes');
    expect($result->mimeType())->toBe('image/png');
    expect($result->width)->toBe(300);
    expect($result->height)->toBe(400);
});

it('returns base64 encoded content', function () {
    $result = new ThumbnailResult(
        content: 'fake-image-bytes',
        mimeType: 'image/png',
        width: 300,
        height: 400,
    );

    expect($result->base64())->toBe(base64_encode('fake-image-bytes'));
});

it('returns data URI', function () {
    $result = new ThumbnailResult(
        content: 'fake-image-bytes',
        mimeType: 'image/png',
        width: 300,
        height: 400,
    );

    expect($result->dataUri())->toBe('data:image/png;base64,'.base64_encode('fake-image-bytes'));
});
