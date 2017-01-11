var gulp = require('gulp');
var notify = require("gulp-notify");
var phpcs = require('gulp-phpcs');
var phpunit = require('gulp-phpunit');
var _ = require('lodash');
var runSequence = require('run-sequence');

function customNotify(message) {
	return notify({
        title: 'CRI',
        message: function(file) {
            return message + ': ' + file.relative;
        }
    })
}

gulp.task('default', ['php']);



/**************
 *    PHP     *
 **************/

gulp.task('php', function(callback) {
    return runSequence('php_cs', 'php_unit', callback);
});

gulp.task('php_cs', function (cb) {
    return gulp.src(['src/**/*.php', 'config/*.php', 'tests/*.php', 'tests/**/*.php'])
    // Validate files using PHP Code Sniffer
        .pipe(phpcs({
            bin: '.\\vendor\\bin\\phpcs.bat',
            standard: '.\\vendor\\cakephp\\cakephp-codesniffer\\CakePHP',
            errorSeverity: 1,
            warningSeverity: 1
        }))
        // Log all problems that was found
        .pipe(phpcs.reporter('log'));
});

function testNotification(status, pluginName, override) {
    var options = {
        title:   ( status == 'pass' ) ? 'Tests Passed' : 'Tests Failed',
        message: ( status == 'pass' ) ? 'All tests have passed!' : 'One or more tests failed',
        icon:    __dirname + '/node_modules/gulp-' + pluginName +'/assets/test-' + status + '.png'
    };
    options = _.merge(options, override);
    return options;
}

gulp.task('php_unit', function() {
    gulp.src('phpunit.xml')
        .pipe(phpunit('', {notify: true}))
        .on('error', notify.onError(testNotification('fail', 'phpunit')))
        .pipe(notify(testNotification('pass', 'php_unit')));
});
