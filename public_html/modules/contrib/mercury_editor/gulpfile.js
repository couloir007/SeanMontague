const argv = require('yargs').argv;
var postcss = require('gulp-postcss');
var path = require('path');
var gulp = require('gulp');
var inputs = ['source/**/*.css', './modules/*/source/**/*.css'];

const cssTask = function () {
  return gulp.src(inputs)
    .pipe(postcss())
    .pipe(gulp.dest((fileVinyl) => {
      //changing the base so that it will include the whole path to the file,
      //excluding the filename
      fileVinyl.base = fileVinyl.path.replace(/[^\/]*js$/, '')
      //outputing the file to a /public/ folder instead of /source/,
      //keeping the structure
      return path.relative(process.cwd(), fileVinyl.path.replace('/source/', '/build/').replace(/[^\/]*js$/, ''))
    }));
}

const cssWatch = function () {
  cssTask();
  return gulp.watch(inputs, cssTask);
};

gulp.task('css', argv.watch ? cssWatch : cssTask);

