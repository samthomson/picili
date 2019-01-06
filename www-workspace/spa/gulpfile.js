/*
note on required font files that css libraries request

semantic.css (from node modules)
- ./themes/default/assets/fonts/
- ./themes/default/assets/images
fontawesome.css  (from node modules)
- ../fonts/
iconmoon (in picili.sass)
- ./themes/default/assets/fonts
*/



var gulp = require('gulp');
var scss = require('gulp-scss');

var sass = require('gulp-sass');
var rename = require('gulp-rename');
var concatCss = require('gulp-concat-css');

var concat = require('gulp-concat');

gulp.task('copy-icomoon-fonts', function(){
    return gulp.src([
        /* icomoon */
        'src/assets/vendor/icomoon/*',
    ])
    .pipe(gulp.dest('src/assets/compiled/css/themes/default/assets/fonts'));
});
gulp.task('copy-semantic-fonts', function(){
    return gulp.src([
        /* semantic */
        'node_modules/semantic-ui-css/themes/default/assets/fonts/*'
    ])
    .pipe(gulp.dest('src/assets/compiled/themes/default/assets/fonts'));
});
gulp.task('copy-fa-fonts', function(){
    return gulp.src([
        /* font awesome */
        'node_modules/font-awesome/fonts/*',
    ])
    .pipe(gulp.dest('src/assets/compiled/fonts'));
});
gulp.task('copy-semantic-images', function(){
    return gulp.src([
        /* semantic */
        'node_modules/semantic-ui-css/themes/default/assets/images/*'
    ])
    .pipe(gulp.dest('src/assets/compiled/css/themes/default/assets/images'));
});

gulp.task('sass', function() {

    return gulp.src([
        'src/assets/sass/picili.scss',
        'src/assets/vendor/materialize/materialize-sass.scss'
    ])
    .pipe(sass().on('error', sass.logError))
    .pipe(gulp.dest('src/assets/compiled/css'))
});


gulp.task('concat-css', gulp.series(['sass'], () => {
    return gulp.src(
        [
            'node_modules/font-awesome/css/font-awesome.css',
            'src/assets/compiled/css/materialize-sass.css',

            'node_modules/semantic-ui-css/components/button.css',
            'node_modules/semantic-ui-css/components/container.css',
            'node_modules/semantic-ui-css/components/dimmer.css',
            'node_modules/semantic-ui-css/components/dropdown.css',
            'node_modules/semantic-ui-css/components/form.css',
            'node_modules/semantic-ui-css/components/grid.css',
            'node_modules/semantic-ui-css/components/header.css',
            'node_modules/semantic-ui-css/components/icon.css',
            'node_modules/semantic-ui-css/components/input.css',
            'node_modules/semantic-ui-css/components/label.css',
            'node_modules/semantic-ui-css/components/loader.css',
            'node_modules/semantic-ui-css/components/menu.css',
            'node_modules/semantic-ui-css/components/segment.css',
            'node_modules/semantic-ui-css/components/transition.css',

            'src/assets/compiled/css/picili.css'
        ]
    )
    .pipe(concatCss("compiled.css", {
        rebaseUrls: false
    }))
    .pipe(gulp.dest('src/assets/compiled/css'))
}));

gulp.task('concat-js', function() {
  return gulp.src(
        [
            'node_modules/jquery/dist/jquery.min.js',
            'node_modules/materialize-css/dist/js/materialize.js',
            'node_modules/semantic-ui-css/components/dropdown.js',
            'node_modules/semantic-ui-css/components/transition.js'
        ]
    )
    .pipe(concat('compiled.js'))
    .pipe(gulp.dest('src/assets/compiled/js'));
});
  

gulp.task('default', gulp.series([
    'concat-css',
    'concat-js',
    'copy-icomoon-fonts',
    'copy-semantic-fonts',
    'copy-fa-fonts',
    'copy-semantic-images'
]));


gulp.task('watch', function() {
    console.log('** watching sass **')
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
