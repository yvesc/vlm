const gulp = require('gulp'),
  sass = require('gulp-ruby-sass'),
  autoprefixer = require('gulp-autoprefixer'),
  cssnano = require('gulp-cssnano'),
  jshint = require('gulp-jshint'),
  imagemin = require('gulp-imagemin'),
  rename = require('gulp-rename'),
  concat = require('gulp-concat'),
  notify = require('gulp-notify'),
  cache = require('gulp-cache'),
  livereload = require('gulp-livereload'),
  gutil = require('gulp-util'),
  del = require('del'),
  uglifyes = require('uglify-es'),
  composer = require('gulp-uglify/composer'),
  uglify = composer(uglifyes, console),
  ftp = require('vinyl-ftp'),
  log = require('fancy-log'),
  //debug = require('gulp-debug'),
  inject = require('gulp-inject-string'),
  htmlmin = require('gulp-htmlmin'),
  runsequence = require('run-sequence');

const VLMVersion = 17;

gulp.task('scripts', function()
{
  return gulp.src(['jvlm/*.js', '!jvlm/external/*', '!jvlm/config.js'])
    .pipe(jshint('.jshintrc'))
    .pipe(jshint.reporter('default'))
    .pipe(concat('jvlm_main.js'))
    .pipe(gulp.dest('jvlm/dist'))
    .pipe(rename(
    {
      suffix: '.min'
    }))
    .pipe(uglify())
    .on('error', function(err)
    {
      gutil.log(gutil.colors.red('[Error]'), err.toString());
    })
    //.pipe(gulpDeployFtp('./vlmcode', 'vlm-dev.ddns.net', 21, 'vlm', 'vlm'))
    .pipe(gulp.dest('jvlm/dist'));
});

gulp.task('html', function()
{
  return gulp.src(['jvlm/index.htm'])
    .pipe(rename('index.html'))
    .pipe(inject.prepend("<!-- AUTO GENERATED FILE DO NOT MODIFY YOUR CHANGES WILL GET LOST-->"))
    .pipe(inject.replace('@@JVLMVERSION@@', 'V' + VLMVersion))
    .pipe(inject.replace('@@VLMBUILDATE@@', Date()))
    .pipe(inject.replace('//JVLMBUILD', "= '" + new Date().toUTCString() + "'"))
    .pipe(gulp.dest('jvlm'))
    .on('error', function(err)
    {
      gutil.log(gutil.colors.red('[Error]'), err.toString());
    });
});

gulp.task('html_prod', function()
{
  return gulp.src(['jvlm/index.htm'])
    .pipe(rename('index.html'))
    .pipe(inject.prepend("<!-- AUTO GENERATED FILE DO NOT MODIFY YOUR CHANGES WILL GET LOST-->"))
    .pipe(inject.replace('@@JVLMVERSION@@', 'V' + VLMVersion))
    .pipe(inject.replace('@@VLMBUILDATE@@', Date()))
    .pipe(inject.replace('//JVLMBUILD', "= '" + new Date().toUTCString() + "'"))
    .pipe(inject.replace('dist/jvlm_main.js', 'dist/jvlm_main.min.js'))
    .pipe(inject.replace('dist/jvlm_main.js', 'dist/jvlm_main.min.js'))
    .pipe(htmlmin(
    {
      collapseWhitespace: true,
      removeComments: true,
      removeCommentsFromCDATA: true
    }))
    .pipe(gulp.dest('jvlm'))
    .on('error', function(err)
    {
      gutil.log(gutil.colors.red('[Error]'), err.toString());
    });
});

gulp.task('libs', function()
{
  return gulp.src(['jvlm/external/jquery/jquery-3.2.1.min.js',
      'jvlm/external/jquery-ui/jquery-ui.js', 'jvlm/external/bootstrap-master/js/bootstrap.js',
      'jvlm/external/jquery.csv.js', 'jvlm/external/bootstrap-colorpicker-master/js/bootstrap-colorpicker.js',
      'jvlm/external/footable-bootstrap/js/footable.js', 'jvlm/jquery.ui.touch-punch.js',
      'jvlm/external/store/store.min.js',
      'jvlm/external/verimail/verimail.jquery.min.js', 'jvlm/external/PasswordStrength/jquery.pstrength-min.1.2.js',
      'jvlm/external/moments/moment-with-locales.min.js', 'externals/fullcalendar/fullcalendar.min.js',
      'externals/fullcalendar/locale-all.js', 'jvlm/external/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js'
    ])
    //.pipe(jshint('.jshintrc'))
    //.pipe(jshint.reporter('default'))
    .pipe(concat('jvlm_libs.js'))
    .pipe(gulp.dest('jvlm/dist'))
    .pipe(rename(
    {
      suffix: '.min'
    }))
    .pipe(uglify())
    .on('error', function(err)
    {
      gutil.log(gutil.colors.red('[Error]'), err.toString());
    })
    //.pipe(gulpDeployFtp('./vlmcode', 'vlm-dev.ddns.net', 21, 'vlm', 'vlm'))
    .pipe(gulp.dest('jvlm/dist'));
});




gulp.task('deploy', function()
{

  var conn = ftp.create(
  {
    host: 'vlm-dev.ddns.net',
    user: 'vlm',
    password: 'vlm',
    parallel: 1,
    reload: true,
    log: log //,
    //debug:log
  });

  var globs = [
    'jvlm/dist/*',
    'jvlm/index.html',
    '*.css'
  ];

  // using base = '.' will transfer everything to /public_html correctly
  // turn off buffering in gulp.src for best performance

  return gulp.src(globs,
    {
      base: '.'
      //cwd: '/home/vlm/vlmcode',
      //buffer: true
    })
    //.pipe(debug())
    //.pipe(debug())
    //.pipe(debug())
    .pipe(conn.newerOrDifferentSize('/home/vlm/vlmcode/')) // only upload newer files
    //.pipe(debug())
    .pipe(conn.dest('/home/vlm/vlmcode'))
  //.pipe(debug())
  ;

});

gulp.task('default', function()
{
  return runsequence('html', 'scripts', 'deploy');
});

gulp.task('BuildAll', function()
{
  return runsequence('libs', 'html', 'scripts', 'deploy');
});

gulp.task('BuildProd', function()
{
  return runsequence('libs', 'html_prod', 'scripts', 'deploy');
});