const mix = require('laravel-mix')
const path = require('path')

mix.setPublicPath('/');
mix.browserSync('https://hdzy.local/');

mix.options({
    processCssUrls: false,
});

mix.js('src/js/theme.js', 'build/js')
mix.sass('src/scss/theme.scss', 'build/css');

mix.sourceMaps(false);