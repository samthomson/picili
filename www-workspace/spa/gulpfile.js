
var gulp = require('gulp');
var scss = require('gulp-scss');

var sass = require('gulp-sass');
var rename = require('gulp-rename');
var concatCss = require('gulp-concat-css');

var concat = require('gulp-concat');

gulp.task('copy-fonts', function(){
    return gulp.src([
        /* icomoon */
        'src/assets/vendor/icomoon/*',
        /* font awesome */
        'node_modules/font-awesome/fonts/*'
    ])
    .pipe(gulp.dest('src/assets/fonts'));
});


gulp.task('sass', function(){

    return gulp.src([
        'src/picili.scss',
        'src/materialize-sass.scss'
    ])
        .pipe(sass().on('error', sass.logError))
        .pipe(gulp.dest('src/assets/compiled'));
});


gulp.task('concat-css', ['sass'], function () {
    return gulp.src(
        [
            'node_modules/semantic-ui-css/semantic.min.css',
            'node_modules/font-awesome/css/font-awesome.css',
            'src/assets/compiled/materialize-sass.css',
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
            'node_modules/jquery/dist/jquery.min.js',
            'node_modules/materialize-css/dist/js/materialize.js'
        ]
    )
    .pipe(concat('compiled.js'))
    .pipe(gulp.dest('src/assets/compiled'));
});
  
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