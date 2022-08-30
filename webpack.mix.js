const mix = require('laravel-mix');

// this keeps relative image urls in js/scss intact, else they're converted to absolute
// (unless processCssUrls is set to false, but then no images will be copied to dist/images either...)
mix.setResourceRoot('../'); // eg from /css/here.css to /images/* or /fonts/*

mix.sourceMaps(null, 'source-map');

mix.setPublicPath('client/dist');

mix.sass('client/src/scss/admin-block-tweaks.scss', 'css');
