<?php

use App\Support\PostContent;

test('plain-text bodies become escaped paragraphs', function () {
    expect(PostContent::fromPlainText("First line\nsecond <b>line</b>\n\n\nNew paragraph"))
        ->toBe("<p>First line<br>\nsecond &lt;b&gt;line&lt;/b&gt;</p><p>New paragraph</p>");
});

test('image ids are read from data-image-id', function () {
    expect(PostContent::imageIds('<p>a</p><img data-image-id="7"><img data-image-id="7"><img data-image-id="x"><img>'))
        ->toBe([7]);
});
