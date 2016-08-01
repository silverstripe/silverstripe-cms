const gulp = require('gulp');
const babel = require('gulp-babel');
const notify = require('gulp-notify');
const sass = require('gulp-sass');
const sourcemaps = require('gulp-sourcemaps');
const uglify = require('gulp-uglify');
const gulpUtil = require('gulp-util');
const browserify = require('browserify');
const babelify = require('babelify');
const watchify = require('watchify');
const source = require('vinyl-source-stream');
const buffer = require('vinyl-buffer');
const path = require('path');
const glob = require('glob');
const eventStream = require('event-stream');
const semver = require('semver');
const packageJson = require('./package.json');

const isDev = typeof process.env.npm_config_development !== 'undefined';
process.env.NODE_ENV = isDev ? 'development' : 'production';

const PATHS = {
  MODULES: './node_modules',
  CMS_JS_SRC: './client/src',
  CMS_JS_DIST: './client/dist/js',
  CMS_CSS_SRC: './client/src/styles',
  CMS_CSS_DIST: './client/dist/styles',
};

const babelifyOptions = {
  presets: ['es2015', 'es2015-ie', 'react'],
  plugins: ['transform-object-assign'],
  ignore: /(node_modules|thirdparty)/,
  comments: false,
};

const browserifyOptions = {};
if (isDev) {
  browserifyOptions.debug = true;
  browserifyOptions.cache = {};
  browserifyOptions.packageCache = {};
  browserifyOptions.plugin = [watchify];
}

/**
 * Transforms the passed JavaScript files to UMD modules.
 *
 * @param array files - The files to transform.
 * @param string dest - The output directory.
 * @return object
 */
function transformToUmd(files, dest) {
  return eventStream.merge(files.map((file) => { // eslint-disable-line arrow-body-style
    return gulp.src(file)
      .pipe(babel({
        presets: ['es2015'],
        moduleId: `ss.${path.parse(file).name}`,
        plugins: ['transform-es2015-modules-umd'],
        comments: false,
      }))
      .on('error', notify.onError({
        message: 'Error: <%= error.message %>',
      }))
      .pipe(gulp.dest(dest));
  }));
}

// Make sure the version of Node being used is valid.
if (!semver.satisfies(process.versions.node, packageJson.engines.node)) {
  console.error( // eslint-disable-line no-console
    `Invalid Node.js version. You need to be using ${packageJson.engines.node}` +
    '. If you want to manage multiple Node.js versions try https://github.com/creationix/nvm'
  );
  process.exit(1);
}

gulp.task('build', ['umd-cms', 'umd-watch', 'bundle-legacy']);

gulp.task('bundle-legacy', function bundleLeftAndMain() {
  const bundleFileName = 'bundle-legacy.js';

  return browserify(Object.assign(
      {},
      browserifyOptions,
      { entries: `${PATHS.CMS_JS_SRC}/bundles/legacy.js` })
  )
    .on('update', bundleLeftAndMain)
    .on('log', (msg) => gulpUtil.log('Finished', `bundled ${bundleFileName} ${msg}`))
    .transform(babelify, babelifyOptions)
    .external('jQuery')
    .external('i18n')
    .external('lib/Router')
    .bundle()
    .on('error', notify.onError({ message: `${bundleFileName}: <%= error.message %>` }))
    .pipe(source(bundleFileName))
    .pipe(buffer())
    .pipe(sourcemaps.init({ loadMaps: true }))
    .pipe(uglify())
    .pipe(sourcemaps.write('./'))
    .pipe(gulp.dest(PATHS.CMS_JS_DIST));
});

gulp.task('umd-cms', () => { // eslint-disable-line
  return transformToUmd(glob.sync(
    `${PATHS.CMS_JS_SRC}/**/*.js`,
    { ignore: `${PATHS.CMS_JS_SRC}/bundles/*` }
  ), PATHS.CMS_JS_DIST);
});

gulp.task('umd-watch', () => { // eslint-disable-line
  if (isDev) {
    gulp.watch(`${PATHS.CMS_JS_SRC}/**/*.js`, ['umd-cms']);
  }
});

gulp.task('css', ['compile:css'], () => { // eslint-disable-line
  if (isDev) {
    gulp.watch(`${PATHS.CMS_CSS_SRC}/**/*.scss`, ['compile:css']);
    gulp.watch(`${PATHS.CMS_JS_SRC}/**/*.scss`, ['compile:css']);
  }
});

gulp.task('compile:css', () => { // eslint-disable-line
  const outputStyle = isDev ? 'expanded' : 'compressed';

  return gulp.src(`${PATHS.CMS_CSS_SRC}/**/*.scss`)
    .pipe(sourcemaps.init())
    .pipe(sass({ outputStyle })
      .on('error', notify.onError({
        message: 'Error: <%= error.message %>',
      }))
  )
  .pipe(sourcemaps.write())
  .pipe(gulp.dest(PATHS.CMS_CSS_DIST));
});
