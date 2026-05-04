(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var ColorPalette = wp.blockEditor.ColorPalette;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var ToggleControl = wp.components.ToggleControl;
    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var __ = wp.i18n.__;

    var defaults = chuck365Defaults.borderColor || {};

    registerBlockType('chuck365/viewer', {
        edit: function (props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            var border = attributes.borderColor || defaults.borderColor || '#f39c12';
            var bg     = attributes.bgColor     || defaults.bgColor     || '#ffffff';
            var color  = attributes.textColor   || defaults.textColor   || '#222222';
            var title  = attributes.title       || defaults.title       || 'Chuck Fact';

            var blockProps = useBlockProps({
                className: 'cn-main-box',
                style: {
                    '--chuck-border': border,
                    '--chuck-bg': bg,
                    '--chuck-text': color,
                }
            });

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Configuration Style', 'chuck365-fact-viewer') },
                        el(TextControl, {
                            label: __('Titre', 'chuck365-fact-viewer'),
                            value: title,
                            onChange: function (val) { setAttributes({ title: val }); }
                        }),
                        el('p', {}, __('Couleur de Bordure', 'chuck365-fact-viewer')),
                        el(ColorPalette, {
                            value: border,
                            onChange: function (val) { setAttributes({ borderColor: val }); }
                        }),
                        el('p', {}, __('Couleur de Fond', 'chuck365-fact-viewer')),
                        el(ColorPalette, {
                            value: bg,
                            onChange: function (val) { setAttributes({ bgColor: val }); }
                        }),
                        el('p', {}, __('Couleur du Texte', 'chuck365-fact-viewer')),
                        el(ColorPalette, {
                            value: color,
                            onChange: function (val) { setAttributes({ textColor: val }); }
                        }),
                        el(ToggleControl, {
                            label: __('Afficher le copyright', 'chuck365-fact-viewer'),
                            checked: attributes.showCopyright,
                            onChange: function (val) { setAttributes({ showCopyright: val }); }
                        })
                    )
                ),
                el('div', blockProps,
                    el('div', { className: 'cn-top-label' },
                        el('span', {}, '🥋 '),
                        el('span', { className: 'cn-title-text' }, title)
                    ),
                    el('div', { className: 'cn-content-area' },
                        el('span', { className: 'cn-quote-mark' }, '"'),
                        __('Le fait de Chuck Norris s\'affichera ici.', 'chuck365-fact-viewer')
                    ),
                    el('div', { className: 'cn-bottom-bar' },
                        el('div', { className: 'cn-copy-wrapper' },
                            el('span', { className: 'cn-copy-info' }, '© ' + new Date().getFullYear() + ' — Chuck365')
                        )
                    )
                )
            );
        },
        save: function () { return null; }
    });
})(window.wp);