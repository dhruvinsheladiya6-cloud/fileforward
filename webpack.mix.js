const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

// JS files
mix.js('resources/assets/js/application.js', 'public/assets/js')
    .js('resources/assets/js/extrachange.js', 'public/assets/js')


// CSS files
mix.css('resources/assets/css/application.css', 'public/assets/css')
    .css('resources/assets/css/authentication.css', 'public/assets/css')
    .css('resources/assets/css/dashboard.css', 'public/assets/css');


mix.options({
    processCssUrls: false
});

mix.version();