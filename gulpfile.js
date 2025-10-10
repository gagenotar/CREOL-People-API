const fs           = require('fs');
const browserSync  = require('browser-sync').create();
const gulp         = require('gulp');
const _autoprefixer = require('gulp-autoprefixer');
const autoprefixer = _autoprefixer && _autoprefixer.default ? _autoprefixer.default : _autoprefixer;
const cleanCSS     = require('gulp-clean-css');
const include      = require('gulp-include');
const eslint       = require('gulp-eslint-new');
const isFixed      = require('gulp-eslint-if-fixed');
const babel        = require('gulp-babel');
const rename       = require('gulp-rename');
const sass         = require('gulp-sass')(require('sass'));
const uglify       = require('gulp-uglify');
const stylelint = require('stylelint');
const merge        = require('merge');
const {
  exec
} = require('child_process');


let config = {
  src: {
    scssPath: './src/scss',
    jsPath: './src/js'
  },
  dist: {
    cssPath: './static/css',
    jsPath: './static/js'
  },
  packagesPath: './node_modules',
  sync: false,
  syncTarget: 'http://localhost/wordpress/'
};

/* eslint-disable no-sync */
if (fs.existsSync('./gulp-config.json')) {
  const overrides = JSON.parse(fs.readFileSync('./gulp-config.json'));
  config = merge(config, overrides);
}
/* eslint-enable no-sync */


//
// Helper functions
//

// Base SCSS linting function
// SCSS linting using stylelint via gulp-stylelint
function lintSCSS(src) {
  // Use stylelint Node API to lint files. Returns a promise.
  return stylelint.lint({
    files: src,
    configFile: '.stylelintrc.json'
  }).then((result) => {
    if (result.errored) {
      // Print formatted output; do not throw to keep the build non-blocking.
      console.log(result.output);
    } else if (result.output) {
      console.log(result.output);
    }
    return Promise.resolve();
  }).catch((err) => {
    console.log(err.stack || err);
    return Promise.resolve();
  });
}

// Base SCSS compile function
function buildCSS(src, dest) {
  dest = dest || config.dist.cssPath;

  return gulp.src(src)
    .pipe(sass({
      includePaths: [config.src.scssPath, config.packagesPath]
    })
      .on('error', sass.logError))
    .pipe(cleanCSS())
    .pipe(autoprefixer({
      // Supported browsers added in package.json ("browserslist")
      cascade: false
    }))
    .pipe(rename({
      extname: '.min.css'
    }))
    .pipe(gulp.dest(dest));
}

// Base JS linting function (with eslint). Fixes problems in-place.
function lintJS(src, dest) {
  dest = dest || config.src.jsPath;
  // Use ESLint CLI to avoid stream completion issues with gulp.
  const globs = Array.isArray(src) ? src.join(' ') : src;
  const cmd = `npx eslint --fix ${globs}`;

  return new Promise((resolve) => {
    exec(cmd, {
      maxBuffer: 1024 * 1024
    }, (err, stdout, stderr) => {
      if (err) {
        // Log the error object so maintainers can inspect failures from the CLI
        console.error('ESLint CLI execution error:', err);
      }
      if (stdout) {
        console.log(stdout);
      }
      if (stderr) {
        console.error(stderr);
      }
      // Resolve regardless of errors to keep build moving; maintainers should
      // inspect output and fix lint errors as needed.
      resolve();
    });
  });
}

// Base JS compile function
function buildJS(src, dest) {
  dest = dest || config.dist.jsPath;

  return gulp.src(src)
    .pipe(include({
      includePaths: [config.packagesPath, config.src.jsPath]
    }))
    .on('error', console.log)
    .pipe(babel())
    .pipe(uglify())
    .pipe(rename({
      extname: '.min.js'
    }))
    .pipe(gulp.dest(dest));
}

// BrowserSync reload function
function serverReload(done) {
  if (config.sync) {
    browserSync.reload();
  }
  done();
}

// BrowserSync serve function
function serverServe(done) {
  if (config.sync) {
    browserSync.init({
      proxy: {
        target: config.syncTarget
      }
    });
  }
  done();
}


//
// CSS
//

// Lint all plugin scss files
gulp.task('scss-lint-plugin', () => {
  return lintSCSS(`${config.src.scssPath}/**/*.scss`);
});

// Compile plugin stylesheet
gulp.task('scss-build-plugin', () => {
  return buildCSS(`${config.src.scssPath}/style.scss`);
});

// All plugin css-related tasks
gulp.task('css', gulp.series('scss-lint-plugin', 'scss-build-plugin'));


//
// JavaScript
//

// Run eslint on js files in src.jsPath
gulp.task('es-lint-plugin', () => {
  return lintJS([`${config.src.jsPath}/**/*.js`], config.src.jsPath);
});

// Concat and uglify js files through babel
gulp.task('js-build-plugin', () => {
  return buildJS(`${config.src.jsPath}/script.js`, config.dist.jsPath);
});

// All js-related tasks
gulp.task('js', gulp.series('es-lint-plugin', 'js-build-plugin'));


//
// Documentation
//

// README generation removed: maintain `README.md` directly in the repo.


//
// Rerun tasks when files change
//
gulp.task('watch', (done) => {
  serverServe(done);

  gulp.watch(`${config.src.scssPath}/**/*.scss`, gulp.series('css', serverReload));
  gulp.watch(`${config.src.jsPath}/**/*.js`, gulp.series('js', serverReload));
  gulp.watch('./**/*.php', gulp.series(serverReload));
});


//
// Default task
//
// 'readme' task removed intentionally; README.md is maintained directly.
// Default task should only run css and js build steps.
gulp.task('default', gulp.series('css', 'js'));
