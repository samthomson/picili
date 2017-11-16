
var gulp = require('gulp');
var sass = require('gulp-sass');
var concatCss = require('gulp-concat-css');

var concat = require('gulp-concat');

// gulp.task('copy-sass', function(){
//     return gulp.src(['node_modules/bootstrap-sass/assets/stylesheets/bootstrap/**/*']).pipe(gulp.dest('src/assets/sass/bootstrap'));
// });

gulp.task('sass', function(){
    return gulp.src(
        [
            'src/picili.scss'
        ]
    )
    .pipe(sass()) // Using gulp-sass
    .pipe(sass.sync().on('error', sass.logError))
    .pipe(gulp.dest('src/assets/compiled'))
});


gulp.task('concat-css', function () {
    return gulp.src(
        [
            'src/assets/vendor/semantic/semantic.min.css',
            'src/assets/compiled/picili.css',
            'src/assets/vendor/fa/css/font-awesome.min.css'
        ]
    )
       .pipe(concatCss("compiled.css"))
       .pipe(gulp.dest('src/assets/compiled/'));
});

gulp.task('concat-js', function() {
  return gulp.src(
        [
            'node_modules/jquery/dist/jquery.min.js'
			/*'node_modules/bootstrap-sass/assets/javascripts/bootstrap.min.js',
            'src/assets/vendor/semantic-ui/semantic-ui.min.js'*/
            /*,
            'src/assets/bootstrap/js/bootstrap.min.js'*/
        ]
    )
    .pipe(concat('compiled.js'))
    .pipe(gulp.dest('src/assets/compiled'));
});

gulp.task('default', gulp.series('sass', 'concat-css', 'concat-js'));


gulp.task('watch', function(){
	gulp.watch('src/**/*.scss', gulp.series('default'));
  // Other watchers
})

gulp.task('dist', function(){
	return gulp.src(['./dist/**/*']).pipe(gulp.dest('./../user-api-laravel/public'));
})

gulp.task('watch-dist', function(){
	gulp.watch('dist/**/*.*', gulp.series('dist'));
  // Other watchers
})