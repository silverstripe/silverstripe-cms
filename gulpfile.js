var gulp = require('gulp')
var babel = require('gulp-babel')
var notify = require('gulp-notify')
var uglify = require('gulp-uglify')
var gulpUtil = require('gulp-util')
var browserify = require('browserify')
var babelify = require('babelify')
var watchify = require('watchify')
var source = require('vinyl-source-stream')
var buffer = require('vinyl-buffer')
var path = require('path')
var glob = require('glob')
var eventStream = require('event-stream')
var semver = require('semver')
var packageJson = require('./package.json')

var PATHS = {
  MODULES: './node_modules',
  CMS_JAVASCRIPT_SRC: './javascript/src',
  CMS_JAVASCRIPT_DIST: './javascript/dist'
}

var browserifyOptions = {
  cache: {},
  packageCache: {},
  poll: true,
  plugin: [watchify]
}

/**
 * Transforms the passed JavaScript files to UMD modules.
 *
 * @param array files - The files to transform.
 * @param string dest - The output directory.
 * @return object
 */
function transformToUmd (files, dest) {
  return eventStream.merge(files.map(function (file) {
    return gulp.src(file)
      .pipe(babel({
        presets: ['es2015'],
        moduleId: 'ss.' + path.parse(file).name,
        plugins: ['transform-es2015-modules-umd']
      }))
      .on('error', notify.onError({
        message: 'Error: <%= error.message %>'
      }))
      .pipe(gulp.dest(dest))
  }))
}

// Make sure the version of Node being used is valid.
if (!semver.satisfies(process.versions.node, packageJson.engines.node)) {
  console.error('Invalid Node.js version. You need to be using ' + packageJson.engines.node + '. If you want to manage multiple Node.js versions try https://github.com/creationix/nvm')
  process.exit(1)
}

if (process.env.npm_config_development) {
  browserifyOptions.debug = true
}

gulp.task('build', ['umd-cms', 'umd-watch', 'bundle-lib'])

gulp.task('bundle-lib', function bundleLib () {
  var stream = browserify(Object.assign({}, browserifyOptions, {
    entries: PATHS.CMS_JAVASCRIPT_SRC + '/bundles/lib.js'
  }))
    .transform(babelify.configure({
      presets: ['es2015'],
      ignore: /(thirdparty)/
    }))
    .on('log', function (msg) {
      gulpUtil.log('Finished bundle-lib.js ' + msg)
    })
    .on('update', bundleLib)
    .external('jQuery')
    .external('i18n')
    .bundle()
    .on('error', notify.onError({
      message: 'Error: <%= error.message %>'
    }))
    .pipe(source('bundle-lib.js'))
    .pipe(buffer())

  if (typeof process.env.npm_config_development === 'undefined') {
    stream.pipe(uglify())
  }

  return stream.pipe(gulp.dest(PATHS.CMS_JAVASCRIPT_DIST))
})

gulp.task('umd-cms', function () {
  return transformToUmd(glob.sync(PATHS.CMS_JAVASCRIPT_SRC + '/*.js'), PATHS.CMS_JAVASCRIPT_DIST)
})

gulp.task('umd-watch', function () {
  gulp.watch(PATHS.CMS_JAVASCRIPT_SRC + '/*.js', ['umd-cms'])
})
