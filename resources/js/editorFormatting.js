import Highlight from '@tiptap/extension-highlight';
import { TextStyle } from '@tiptap/extension-text-style';

/*
 * Preset text formatting for the post editor. Each preset is stored as an `rt-*` class
 * (styled in resources/css/app.css) instead of an inline style, so it can follow dark mode
 * and the server can whitelist it. Keep the values in sync with PostContent::FORMAT_CLASSES.
 */

export const FONTS = [
    { value: 'gothic', label: '프리텐다드' },
    { value: 'serif', label: '본명조' },
    { value: 'round', label: '고운돋움' },
    { value: 'pen', label: '나눔손글씨 펜' },
    { value: 'display', label: '검은고딕' },
];

export const SIZES = [
    { value: 'sm', label: '작게' },
    { value: 'lg', label: '크게' },
    { value: 'xl', label: '아주 크게' },
];

export const COLORS = [
    { value: 'gray', label: '회색' },
    { value: 'red', label: '빨강' },
    { value: 'orange', label: '주황' },
    { value: 'yellow', label: '노랑' },
    { value: 'green', label: '초록' },
    { value: 'blue', label: '파랑' },
    { value: 'purple', label: '보라' },
    { value: 'pink', label: '분홍' },
];

export const HIGHLIGHTS = [
    { value: 'yellow', label: '노랑' },
    { value: 'green', label: '초록' },
    { value: 'blue', label: '파랑' },
    { value: 'pink', label: '분홍' },
    { value: 'purple', label: '보라' },
];

export const PREFIXES = {
    font: 'rt-font-',
    size: 'rt-size-',
    color: 'rt-color-',
    highlight: 'rt-bg-',
};

const presetFromClass = (element, prefix, presets) => {
    const value = Array.from(element.classList)
        .find((name) => name.startsWith(prefix))
        ?.slice(prefix.length);

    return presets.some((preset) => preset.value === value) ? value : null;
};

// A mark attribute stored as one preset class, e.g. { font: 'serif' } <-> class="rt-font-serif".
const presetAttribute = (name, prefix, presets, fallback = null) => ({
    default: fallback,
    parseHTML: (element) =>
        presetFromClass(element, prefix, presets) ?? fallback,
    renderHTML: (attributes) =>
        attributes[name] ? { class: `${prefix}${attributes[name]}` } : {},
});

const TEXT_STYLE_PRESETS = [
    [PREFIXES.font, FONTS],
    [PREFIXES.size, SIZES],
    [PREFIXES.color, COLORS],
];

// Font, size, and color share one <span>. Only spans carrying a preset class become a
// text style, so inline styles pasted from other sites are dropped.
export const FormattedText = TextStyle.extend({
    parseHTML() {
        return [
            {
                tag: 'span',
                consuming: false,
                getAttrs: (element) =>
                    TEXT_STYLE_PRESETS.some(([prefix, presets]) =>
                        presetFromClass(element, prefix, presets),
                    )
                        ? {}
                        : false,
            },
        ];
    },

    addAttributes() {
        return {
            font: presetAttribute('font', PREFIXES.font, FONTS),
            size: presetAttribute('size', PREFIXES.size, SIZES),
            color: presetAttribute('color', PREFIXES.color, COLORS),
        };
    },
});

// <mark class="rt-bg-…">; a bare <mark> (e.g. from ==text== or Mod-Shift-H) is yellow.
export const PresetHighlight = Highlight.extend({
    addAttributes() {
        return {
            color: presetAttribute(
                'color',
                PREFIXES.highlight,
                HIGHLIGHTS,
                'yellow',
            ),
        };
    },
});
