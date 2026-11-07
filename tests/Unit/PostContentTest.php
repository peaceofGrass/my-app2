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

test('preset formatting classes survive sanitizing', function () {
    $html = '<p><span class="rt-font-serif rt-size-lg rt-color-red">a</span> <mark class="rt-bg-green">b</mark> <mark>c</mark></p>';

    expect(PostContent::sanitize($html))->toBe($html);
});

test('unknown classes are dropped and the preset ones kept', function () {
    expect(PostContent::sanitize('<p><span class="rt-color-blue fixed inset-0 rt-color-blue rt-bg-yellow">a</span><mark class="rt-color-red rt-bg-pink">b</mark></p>'))
        ->toBe('<p><span class="rt-color-blue">a</span><mark class="rt-bg-pink">b</mark></p>');
});

test('inline styles and classes on other elements are stripped', function () {
    $html = '<p class="rt-color-red" style="color:red">'
        .'<span style="position:fixed;inset:0;background-image:url(https://evil.test/x.png)">a</span>'
        .'<mark style="background-color:red" data-color="red">b</mark>'
        .'<span class="rt-font-comic-sans">c</span></p>';

    expect(PostContent::sanitize($html))->toBe('<p><span>a</span><mark>b</mark><span>c</span></p>');
});

test('every whitelisted formatting class is styled', function () {
    $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/app.css');

    foreach (PostContent::FORMAT_CLASSES as $classes) {
        foreach ($classes as $class) {
            expect($css)->toContain(".{$class} {");
        }
    }
});
