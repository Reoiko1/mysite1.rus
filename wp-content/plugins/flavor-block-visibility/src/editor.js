/**
 * Flavor Block Visibility — Editor Script
 *
 * Adds "Responsive Conditions" toggles into the Advanced panel
 * of every Gutenberg block.
 *
 * @package flavor-block-visibility
 */
( function () {
    var compose     = wp.compose;
    var element     = wp.element;
    var blockEditor = wp.blockEditor;
    var components  = wp.components;
    var hooks       = wp.hooks;
    var i18n        = wp.i18n;

    var createHigherOrderComponent = compose.createHigherOrderComponent;
    var Fragment    = element.Fragment;
    var el         = element.createElement;
    var InspectorAdvancedControls = blockEditor.InspectorAdvancedControls;
    var ToggleControl = components.ToggleControl;
    var addFilter  = hooks.addFilter;
    var __         = i18n.__;

    // Get breakpoints from PHP (passed via wp_localize_script).
    var bp = ( window.flbvisSettings && window.flbvisSettings.breakpoints ) || {
        mobile_max: 767,
        tablet_min: 768,
        tablet_max: 1024,
        desktop_min: 1025
    };

    /**
     * 1. Extend block attributes.
     */
    function addResponsiveAttributes( settings ) {
        if ( ! settings.attributes ) {
            settings.attributes = {};
        }

        settings.attributes.flbvisHideOnDesktop = {
            type: 'boolean',
            default: false,
        };
        settings.attributes.flbvisHideOnTablet = {
            type: 'boolean',
            default: false,
        };
        settings.attributes.flbvisHideOnMobile = {
            type: 'boolean',
            default: false,
        };

        return settings;
    }

    addFilter(
        'blocks.registerBlockType',
        'flavor-block-visibility/add-responsive-attributes',
        addResponsiveAttributes
    );

    /**
     * 2. Add toggle controls to the Advanced panel.
     */
    var withResponsiveControls = createHigherOrderComponent( function ( BlockEdit ) {
        return function ( props ) {
            var attributes    = props.attributes;
            var setAttributes = props.setAttributes;
            var isSelected    = props.isSelected;

            var hideOnDesktop = attributes.flbvisHideOnDesktop;
            var hideOnTablet  = attributes.flbvisHideOnTablet;
            var hideOnMobile  = attributes.flbvisHideOnMobile;

            return el(
                Fragment,
                null,
                el( BlockEdit, props ),
                isSelected &&
                    el(
                        InspectorAdvancedControls,
                        null,
                        el(
                            'div',
                            { className: 'flbvis-responsive-conditions' },
                            el(
                                'h3',
                                { className: 'flbvis-responsive-conditions__title' },
                                el(
                                    'svg',
                                    {
                                        className: 'flbvis-responsive-conditions__icon',
                                        width: 16,
                                        height: 16,
                                        viewBox: '0 0 24 24',
                                        fill: 'none',
                                        xmlns: 'http://www.w3.org/2000/svg',
                                        'aria-hidden': 'true',
                                        focusable: 'false'
                                    },
                                    el( 'path', {
                                        d: 'M4 6C4 4.89543 4.89543 4 6 4H18C19.1046 4 20 4.89543 20 6V15C20 16.1046 19.1046 17 18 17H6C4.89543 17 4 16.1046 4 15V6Z',
                                        stroke: 'currentColor',
                                        strokeWidth: '1.5',
                                        strokeLinecap: 'round',
                                        strokeLinejoin: 'round'
                                    }),
                                    el( 'path', {
                                        d: 'M9 20H15',
                                        stroke: 'currentColor',
                                        strokeWidth: '1.5',
                                        strokeLinecap: 'round'
                                    }),
                                    el( 'path', {
                                        d: 'M12 17V20',
                                        stroke: 'currentColor',
                                        strokeWidth: '1.5',
                                        strokeLinecap: 'round'
                                    })
                                ),
                                __( 'Responsive Conditions', 'flavor-block-visibility' )
                            ),
                            el( ToggleControl, {
                                label: __( 'Hide on Desktop', 'flavor-block-visibility' ),
                                help: hideOnDesktop
                                    ? __( 'Hidden on screens', 'flavor-block-visibility' ) + ' \u2265 ' + bp.desktop_min + 'px'
                                    : '',
                                checked: !! hideOnDesktop,
                                onChange: function ( val ) {
                                    setAttributes( { flbvisHideOnDesktop: val } );
                                },
                            } ),
                            el( ToggleControl, {
                                label: __( 'Hide on Tablet', 'flavor-block-visibility' ),
                                help: hideOnTablet
                                    ? __( 'Hidden on screens', 'flavor-block-visibility' ) + ' ' + bp.tablet_min + 'px \u2013 ' + bp.tablet_max + 'px'
                                    : '',
                                checked: !! hideOnTablet,
                                onChange: function ( val ) {
                                    setAttributes( { flbvisHideOnTablet: val } );
                                },
                            } ),
                            el( ToggleControl, {
                                label: __( 'Hide on Mobile', 'flavor-block-visibility' ),
                                help: hideOnMobile
                                    ? __( 'Hidden on screens', 'flavor-block-visibility' ) + ' \u2264 ' + bp.mobile_max + 'px'
                                    : '',
                                checked: !! hideOnMobile,
                                onChange: function ( val ) {
                                    setAttributes( { flbvisHideOnMobile: val } );
                                },
                            } )
                        )
                    )
            );
        };
    }, 'withResponsiveControls' );

    addFilter(
        'editor.BlockEdit',
        'flavor-block-visibility/with-responsive-controls',
        withResponsiveControls
    );

    /**
     * 3. Add CSS classes to the block wrapper in the editor.
     */
    function addResponsiveClasses( extraProps, blockType, attributes ) {
        var classes = [];

        if ( attributes.flbvisHideOnDesktop ) {
            classes.push( 'flbvis-hide-desktop' );
        }
        if ( attributes.flbvisHideOnTablet ) {
            classes.push( 'flbvis-hide-tablet' );
        }
        if ( attributes.flbvisHideOnMobile ) {
            classes.push( 'flbvis-hide-mobile' );
        }

        if ( classes.length > 0 ) {
            extraProps.className = ( extraProps.className || '' ) + ' ' + classes.join( ' ' );
        }

        return extraProps;
    }

    addFilter(
        'blocks.getSaveContent.extraProps',
        'flavor-block-visibility/add-responsive-classes',
        addResponsiveClasses
    );
} )();
