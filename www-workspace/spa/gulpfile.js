
var gulp = require('gulp');
var scss = require('gulp-scss');

var sass = require('gulp-sass');
var rename = require('gulp-rename');
var concatCss = require('gulp-concat-css');

var concat = require('gulp-concat');

gulp.task('copy-fonts', function(){
    return gulp.src([
        /* font awesome */
        'src/assets/vendor/fa/fonts/*',
        /* icomoon */
        'src/assets/vendor/icomoon/*'
    ])
    .pipe(gulp.dest('src/assets/fonts'));
});

gulp.task('sass', function(){

    return gulp.src('src/picili.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(gulp.dest('src/assets/compiled'));
});


gulp.task('concat-css', ['sass'], function () {
    return gulp.src(
        [
            'node_modules/semantic-ui-css/semantic.min.css',
            'src/assets/vendor/fa/css/font-awesome.css',
            'src/assets/compiled/picili.css'
        ]
    )
    .pipe(concatCss("compiled.css", {
        rebaseUrls: false
    }))
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

// gulp.task('default', gulp.series('sass', 'concat-css', 'concat-js'));

  
gulp.task('default', [
        'concat-css', /* depends on 'sass' task */
        'concat-js',
        'copy-fonts'
    ], function () {
        console.log('all done..')
    }
);


gulp.task('watch', function() {
    console.log('** watch **')
	gulp.watch('src/**/*.scss', ['default']);
  // Other watchers
})

gulp.task('dist', function(){
	return gulp.src(['./dist/**/*']).pipe(gulp.dest('./../user-api-laravel/public'));
})

gulp.task('watch-dist', function(){
	gulp.watch('dist/**/*.*', gulp.series('dist'));
  // Other watchers
})