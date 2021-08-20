module.exports = function(grunt) {
    'use strict';

    var sass    = grunt.config('sass') || {};
    var watch   = grunt.config('watch') || {};
    var notify  = grunt.config('notify') || {};
    var root    = grunt.option('root') + '/taoBackOffice/views/';

    sass.taobackoffice = {
        files : {}
    };
    sass.taobackoffice.files[root + 'css/list.css'] = root + 'scss/list.scss';

    watch.taobackofficesass = {
        files : [root + 'scss/*.scss'],
        tasks : ['sass:taobackoffice', 'notify:taobackoffice'],
        options : {
            debounceDelay : 1000
        }
    };

    notify.taobackofficesass = {
        options: {
            title: 'Grunt SASS',
            message: 'SASS files compiled to CSS'
        }
    };

    grunt.config('sass', sass);
    grunt.config('watch', watch);
    grunt.config('notify', notify);

    //register an alias for main build
    grunt.registerTask('taobackofficesass', ['sass:taobackoffice']);
};
