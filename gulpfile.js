var gulp = require('gulp'),
    babel = require('gulp-babel'),
    diff = require('gulp-diff'),
    notify = require('gulp-notify'),
    sass = require('gulp-sass'),
    sourcemaps = require('gulp-sourcemaps'),
    uglify = require('gulp-uglify');
    gulpUtil = require('gulp-util'),
    browserify = require('browserify'),
    babelify = require('babelify'),
    watchify = require('watchify'),
    source = require('vinyl-source-stream'),
    buffer = require('vinyl-buffer'),
    path = require('path'),
    glob = require('glob'),
    eventStream = require('event-stream'),
    semver = require('semver'),
    packageJson = require('./package.json');
    
var isDev = typeof process.env.npm_config_development !== 'undefined';

var PATHS = {
    MODULES: './node_modules',
    CMS_JAVASCRIPT_SRC: './javascript/src',
    CMS_JAVASCRIPT_DIST: './javascript/dist',
    CMS_SCSS: './scss',
    CMS_CSS: './css'
};

var browserifyOptions = {
    cache: {},
    packageCache: {},
    poll: true,
    plugin: [watchify]
};

/**
 * Transforms the passed JavaScript files to UMD modules.
 *
 * @param array files - The files to transform.
 * @param string dest - The output directory.
 * @return object
 */
function transformToUmd(files, dest) {
    return eventStream.merge(files.map(function (file) {
        return gulp.src(file)
            .pipe(babel({
                presets: ['es2015'],
                moduleId: 'ss.' + path.parse(file).name,
                plugins: ['transform-es2015-modules-umd']
            }))
            .on('error', notify.onError({
                message: 'Error: <%= error.message %>',
            }))
            .pipe(gulp.dest(dest));
    }));
}

// Make sure the version of Node being used is valid.
if (!semver.satisfies(process.versions.node, packageJson.engines.node)) {
    console.error('Invalid Node.js version. You need to be using ' + packageJson.engines.node + '. If you want to manage multiple Node.js versions try https://github.com/creationix/nvm');
    process.exit(1);
}

if (isDev) {
    browserifyOptions.debug = true;
}

gulp.task('build', ['umd-cms', 'umd-watch', 'bundle-lib']);

gulp.task('bundle-lib', function bundleLib() {
    var stream = browserify(Object.assign({}, browserifyOptions, {
            entries: PATHS.CMS_JAVASCRIPT_SRC + '/bundles/lib.js'
        }))
        .transform(babelify.configure({
            presets: ['es2015'],
            ignore: /(thirdparty)/
        }))
        .on('log', function (msg) { gulpUtil.log('Finished bundle-lib.js ' + msg); })
        .on('update', bundleLib)
        .external('jQuery')
        .external('i18n')
        .bundle()
        .on('error', notify.onError({
            message: 'Error: <%= error.message %>',
        }))
        .pipe(source('bundle-lib.js'))
        .pipe(buffer());

    if (!isDev) {
        stream.pipe(uglify());
    }

    return stream.pipe(gulp.dest(PATHS.CMS_JAVASCRIPT_DIST));
});

gulp.task('umd-cms', function () {
    return transformToUmd(glob.sync(PATHS.CMS_JAVASCRIPT_SRC + '/*.js'), PATHS.CMS_JAVASCRIPT_DIST);
});

gulp.task('umd-watch', function () {
    gulp.watch(PATHS.CMS_JAVASCRIPT_SRC + '/*.js', ['umd-cms']);
});

gulp.task('compile', function () {
    var outputStyle = isDev ? 'expanded' : 'compressed';
    
    if (isDev) {
        gulp.watch(PATHS.CMS_SCSS + '/**/*.scss', ['compile']);
    }
    
    return gulp.src(PATHS.CMS_SCSS + '/**/*.scss')
        .pipe(sourcemaps.init())
        .pipe(sass({ outputStyle: outputStyle })
            .on('error', notify.onError({
                message: 'Error: <%= error.message %>'
            }))
        )
        .pipe(sourcemaps.write())
        .pipe(gulp.dest(PATHS.CMS_CSS))
});
