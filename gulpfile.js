var gulp = require('gulp'),
    babel = require('gulp-babel'),
    diff = require('gulp-diff'),
    notify = require('gulp-notify'),
    uglify = require('gulp-uglify');
    gulpUtil = require('gulp-util'),
    gulpif = require('gulp-if'),
    browserify = require('browserify'),
    babelify = require('babelify'),
    watchify = require('watchify'),
    source = require('vinyl-source-stream'),
    buffer = require('vinyl-buffer'),
    path = require('path'),
    glob = require('glob'),
    eventStream = require('event-stream'),
    semver = require('semver'),
    packageJson = require('./package.json'),
    sourcemaps = require('gulp-sourcemaps');

var PATHS = {
    MODULES: './node_modules',
    CMS_JAVASCRIPT_SRC: './javascript/src',
    CMS_JAVASCRIPT_DIST: './javascript/dist'
};

var browserifyOptions = {
    cache: {},
    packageCache: {},
    poll: true,
    plugin: [watchify]
};

var isDev = typeof process.env.npm_config_development !== 'undefined';

process.env.NODE_ENV = isDev ? 'development' : 'production';

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
                plugins: ['transform-es2015-modules-umd'],
                comments: false
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

var babelifyOptions = {
	presets: ['es2015', 'react'],
	ignore: /(node_modules|thirdparty)/,
	comments: false
};

gulp.task('build', ['umd-cms', 'umd-watch', 'bundle-legacy']);

gulp.task('bundle-legacy', function bundleLeftAndMain() {
	var bundleFileName = 'bundle-legacy.js';

	return browserify(Object.assign({}, browserifyOptions, { entries: PATHS.CMS_JAVASCRIPT_SRC + '/bundles/legacy.js' }))
		.on('update', bundleLeftAndMain)
		.on('log', function (msg) { gulpUtil.log('Finished', 'bundled ' + bundleFileName + ' ' + msg) })
		.transform('babelify', babelifyOptions)
		.external('jQuery')
		.external('i18n')
		.external('router')
		.bundle()
		.on('error', notify.onError({ message: bundleFileName + ': <%= error.message %>' }))
		.pipe(source(bundleFileName))
		.pipe(buffer())
		.pipe(sourcemaps.init({ loadMaps: true }))
		.pipe(gulpif(!isDev, uglify()))
		.pipe(sourcemaps.write('./'))
		.pipe(gulp.dest(PATHS.CMS_JAVASCRIPT_DIST));
});

gulp.task('umd-cms', function () {
    return transformToUmd(glob.sync(PATHS.CMS_JAVASCRIPT_SRC + '/*.js'), PATHS.CMS_JAVASCRIPT_DIST);
});

gulp.task('umd-watch', function () {
    gulp.watch(PATHS.CMS_JAVASCRIPT_SRC + '/*.js', ['umd-cms']);
});
