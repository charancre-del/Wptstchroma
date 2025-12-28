<?php
/**
 * Critical CSS Injection
 * Inlines essential above-the-fold styles to prevent render-blocking
 *
 * @package Chroma_Excellence
 */

function chroma_print_critical_css()
{
    ?>
    <style id="chroma-critical-css">
        /* Critical Fonts */
        @font-face {
            font-family: 'Outfit';
            font-style: normal;
            font-weight: 300;
            font-display: swap;
            src: url('<?php echo get_template_directory_uri(); ?>/assets/webfonts/Outfit-Light.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Outfit';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('<?php echo get_template_directory_uri(); ?>/assets/webfonts/Outfit-Regular.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Outfit';
            font-style: normal;
            font-weight: 500;
            font-display: swap;
            src: url('<?php echo get_template_directory_uri(); ?>/assets/webfonts/Outfit-Medium.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Outfit';
            font-style: normal;
            font-weight: 600;
            font-display: swap;
            src: url('<?php echo get_template_directory_uri(); ?>/assets/webfonts/Outfit-SemiBold.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Outfit';
            font-style: normal;
            font-weight: 700;
            font-display: swap;
            src: url('<?php echo get_template_directory_uri(); ?>/assets/webfonts/Outfit-Bold.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Playfair Display';
            font-style: normal;
            font-weight: 600;
            font-display: swap;
            src: url('<?php echo get_template_directory_uri(); ?>/assets/webfonts/PlayfairDisplay-SemiBold.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Playfair Display';
            font-style: normal;
            font-weight: 700;
            font-display: swap;
            src: url('<?php echo get_template_directory_uri(); ?>/assets/webfonts/PlayfairDisplay-Bold.woff2') format('woff2');
        }
        @font-face {
            font-family: 'Playfair Display';
            font-style: normal;
            font-weight: 800;
            font-display: swap;
            src: url('<?php echo get_template_directory_uri(); ?>/assets/webfonts/PlayfairDisplay-ExtraBold.woff2') format('woff2');
        }

        /* Critical Reset & Base */
        *,
        ::before,
        ::after {
            box-sizing: border-box;
            border: 0 solid #e5e7eb
        }

        html {
            line-height: 1.5;
            -webkit-text-size-adjust: 100%;
            font-family: Outfit, system-ui, sans-serif
        }

        body {
            margin: 0;
            line-height: inherit;
           	/* Font Metric Overrides to prevent CLS */
	@font-face {
		font-family: 'Outfit-Fallback';
		src: local('Arial');
		ascent-override: 98%;
		descent-override: 24%;
		line-gap-override: 0%;
		size-adjust: 100%;
	}
	@font-face {
		font-family: 'Playfair-Fallback';
		src: local('Georgia');
		ascent-override: 95%;
		descent-override: 20%;
		line-gap-override: 0%;
		size-adjust: 100%;
	}

	body {
		font-family: Outfit, "Outfit-Fallback", system-ui, sans-serif;
	}

	h1, h2, h3, h4, h5, h6, .font-serif {
		font-family: "Playfair Display", "Playfair-Fallback", serif;
	}
            overflow-x: hidden
        }

        /* Critical Layout */
        body,
        html {
            overflow-x: hidden
        }

        .container {
            width: 100%;
            margin-left: auto;
            margin-right: auto
        }

        @media(min-width:640px) {
            .container {
                max-width: 640px
            }
        }

        @media(min-width:768px) {
            .container {
                max-width: 768px
            }
        }

        @media(min-width:1024px) {
            .container {
                max-width: 1024px
            }
        }

        @media(min-width:1280px) {
            .container {
                max-width: 1280px
            }
        }

        @media(min-width:1536px) {
            .container {
                max-width: 1536px
            }
        }

        /* Critical Colors */
        .bg-brand-cream {
            background-color: rgb(255 252 248)
        }

        .bg-white {
            background-color: rgb(255 255 255)
        }

        .bg-white\/85 {
            background-color: rgba(255, 255, 255, .85)
        }

        .bg-brand-ink {
            background-color: rgb(38 50 56)
        }

        .text-brand-ink {
            color: rgb(38 50 56)
        }

        .text-white {
            color: rgb(255 255 255)
        }

        .text-chroma-blue {
            color: rgb(74 108 124)
        }

        .bg-chroma-blue {
            background-color: rgb(74 108 124)
        }

        .bg-chroma-green {
            background-color: rgb(141 163 153)
        }

        .border-chroma-blue\/10 {
            border-color: rgba(74, 108, 124, .1)
        }

        /* Critical Header */
        .sticky {
            position: sticky
        }

        .top-0 {
            top: 0
        }

        .z-40 {
            z-index: 40
        }

        .backdrop-blur-xl {
            -webkit-backdrop-filter: blur(24px);
            backdrop-filter: blur(24px)
        }

        .border-b {
            border-bottom-width: 1px
        }

        .flex {
            display: flex
        }

        .items-center {
            align-items: center
        }

        .justify-between {
            justify-content: space-between
        }

        .gap-3 {
            gap: .75rem
        }

        .gap-8 {
            gap: 2rem
        }

        .h-12 {
            height: 3rem
        }

        .h-\[82px\] {
            height: 82px
        }

        .w-auto {
            width: auto
        }

        .max-w-7xl {
            max-width: 80rem
        }

        .mx-auto {
            margin-left: auto;
            margin-right: auto
        }

        .px-4 {
            padding-left: 1rem;
            padding-right: 1rem
        }

        .w-2 {
            width: 0.5rem
        }

        .h-2 {
            height: 0.5rem
        }

        .hidden {
            display: none
        }

        @media(min-width:768px) {
            .md\:flex {
                display: flex
            }

            .md\:inline-flex {
                display: inline-flex
            }

            .md\:hidden {
                display: none
            }
        }

        @media(min-width:1024px) {
            .lg\:px-6 {
                padding-left: 1.5rem;
                padding-right: 1.5rem
            }
        }

        /* Critical Typography */
        .font-bold {
            font-weight: 700
        }

        .font-semibold {
            font-weight: 600
        }

        .text-xs {
            font-size: .75rem;
            line-height: 1rem
        }

        .text-sm {
            font-size: .875rem;
            line-height: 1.25rem
        }

        .text-lg {
            font-size: 1.125rem;
            line-height: 1.75rem
        }

        .text-\[10px\] {
            font-size: 10px
        }

        .uppercase {
            text-transform: uppercase
        }

        .tracking-\[0\.2em\] {
            letter-spacing: .2em
        }

        .leading-tight {
            line-height: 1.25
        }

        /* Critical Layout Utilities */
        .block {
            display: block
        }

        .w-full {
            width: 100%
        }

        .text-center {
            text-align: center
        }

        .mt-6 {
            margin-top: 1.5rem
        }

        .py-4 {
            padding-top: 1rem;
            padding-bottom: 1rem
        }

        .px-6 {
            padding-left: 1.5rem;
            padding-right: 1.5rem
        }

        .pt-20 {
            padding-top: 5rem
        }

        @media(min-width:1024px) {
            .lg\:pt-24 {
                padding-top: 6rem
            }
        
            .lg\:block {
                display: block
            }
        }

        .rounded-xl {
            border-radius: 0.75rem
        }

        .rounded-full {
            border-radius: 9999px
        }

        .shadow-soft {
            box-shadow: 0 20px 40px -10px rgba(74, 108, 124, .08)
        }

        .transition {
            transition-property: color, background-color, border-color, text-decoration-color, fill, stroke, opacity, box-shadow, transform, filter, backdrop-filter;
            transition-duration: .15s;
            transition-timing-function: cubic-bezier(.4, 0, .2, 1)
        }

        .antialiased {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale
        }

        /* Text Selection */
        ::selection {
            background-color: rgb(214 125 107);
            color: rgb(255 255 255)
        }

        /* Critical Animations */
        @keyframes pulse {
            50% {
                opacity: .5
            }
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .animate-pulse {
            animation: pulse 2s cubic-bezier(.4, 0, .6, 1) infinite
        }

        .fade-in-up {
            animation: fadeInUp 1.1s ease forwards;
            opacity: 0;
            transform: translateY(24px)
        }

        /* Skip link accessibility */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border-width: 0
        }
    </style>
    <?php
}
// Critical CSS enabled to support main.css deferral
add_action('wp_head', 'chroma_print_critical_css', 1);

