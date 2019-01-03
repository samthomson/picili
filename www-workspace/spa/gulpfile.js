
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
        /* semantic */
        'node_modules/semantic-ui-css/themes/default/assets/fonts/*'
    ])
    .pipe(gulp.dest('src/assets/compiled/themes/default/assets/fonts'));
});
gulp.task('more-fonts', function(){
    return gulp.src([
        /* font awesome */
        'node_modules/font-awesome/fonts/*',
    ])
    .pipe(gulp.dest('src/assets/fonts'));
});
gulp.task('copy-semantic-images', function(){
    return gulp.src([
        /* semantic */
        'node_modules/semantic-ui-css/themes/default/assets/images/*'
    ])
    .pipe(gulp.dest('src/assets/compiled/themes/default/assets/images'));
});

gulp.task('sass', function() {

    return gulp.src([
        'src/picili.scss',
        'src/materialize-sass.scss'
    ])
    .pipe(sass().on('error', sass.logError))
    .pipe(gulp.dest('src/assets/compiled'))
});


gulp.task('concat-css', gulp.series(['sass'], () => {
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
    .pipe(gulp.dest('src/assets/compiled'))
}));

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
  

gulp.task('default', gulp.series([
    'concat-css',
    'concat-js',
    'copy-fonts',
    'more-fonts',
    'copy-semantic-images'
]));


gulp.task('watch', function() {
    console.log('** watch **')
    gulp.watch('src/**/*.scss')
        .on('change', () => {
            console.log('SASS changed - running default gulp task')
            const gtDefault = gulp.series(['default'])
            gtDefault()
    });
})

gulp.task('dist', function() {
    // copy dist assets (built project) into api public folder
	return gulp.src(
        [
            './dist/**/*'
        ]
    )
    .pipe(gulp.dest('./../user-api-laravel/public'));
})
