module.exports = function(grunt) {
    'use strict';

    var sass    = grunt.config('sass') || {};
    var watch   = grunt.config('watch') || {};
    var notify  = grunt.config('notify') || {};
    var root    = grunt.option('root') + '/taoTaskQueue/views/';

    sass.taotaskqueue = {};
    sass.taotaskqueue.files = {};

    sass.taotaskqueuecomponents = {};
    sass.taotaskqueuecomponents.files = {};
    sass.taotaskqueuecomponents.files[root + 'js/component/manager/css/manager.css'] = root + 'js/component/manager/scss/manager.scss';
    sass.taotaskqueuecomponents.files[root + 'js/component/listing/css/element.css'] = root + 'js/component/listing/scss/element.scss';
    sass.taotaskqueuecomponents.files[root + 'js/component/listing/css/list.css'] = root + 'js/component/listing/scss/list.scss';
    sass.taotaskqueuecomponents.files[root + 'js/component/listing/css/report.css'] = root + 'js/component/listing/scss/report.scss';

    watch.taotaskqueuesass = {
        files : [root + 'scss/**/*.scss'],
        tasks : ['notify:taotaskqueuesass'],
        options : {
            debounceDelay : 1000
        }
    };

    watch.taotaskqueuecomponents = {
        files: [root + 'js/component/**/scss/**/*.scss'],
        tasks: ['sass:taotaskqueuecomponents', 'notify:taotaskqueuesass'],
        options: {
            debounceDelay: 600
        }
    };

    notify.taotaskqueuesass = {
        options: {
            title: 'Grunt SASS',
            message: 'SASS files compiled to CSS'
        }
    };

    grunt.config('sass', sass);
    grunt.config('watch', watch);
    grunt.config('notify', notify);

    //register an alias for main build
    grunt.registerTask('taotaskqueuesass', ['sass:taotaskqueuecomponents']);
};
