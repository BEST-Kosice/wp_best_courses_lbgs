/* jshint node:true */
module.exports = function (grunt) {
    'use strict';

    grunt.initConfig({
        // setting folder templates
        dirs: {
            css: 'assets/css',
            sass: 'assets/sass',
            scss: 'assets/scss',
            js: 'assets/js'
        },

        //  Compile all  sass .scss files
        sass: {
            options: {
                sourceMap: true,
                sourceMapEmbed: false,
                sourceMapContents: true,
                includePaths: ['.']
            },
            default: {
                files: [{
                    expand: true,
                    cwd: '<%= dirs.scss %>/',
                    src: ['*.{scss,sass}'],
                    dest: '<%= dirs.css %>/',
                    ext: '.css'
                }]
            }

        },



        // Compile all .less files.
        less: {
            compile: {
                options: {
                    // These paths are searched for @imports
                    paths: ['<%= less.css %>/']
                },
                files: [{
                    expand: true,
                    cwd: '<%= dirs.css %>/',
                    src: [
                        '*.less',
                        '!mixins.less'
                    ],
                    dest: '<%= dirs.css %>/',
                    ext: '.css'
                }]
            }
        },

        // Minify all .css files.
        cssmin: {
            minify: {
                expand: true,
                cwd: '<%= dirs.css %>/',
                src: ['*.css'],
                dest: '<%= dirs.css %>/',
                ext: '.css'
            }
        },

        cssnano: {
            options: {
                sourcemap: false,
                mergeLonghand: true,
                autoprefixer: {browsers: 'last 2 versions'},
            },
            dist: {
                files: {
                    '<%= dirs.css %>/frontend.css': '<%= dirs.css %>/frontend.css'
                }
            }
        },



        // Minify .js files.
        uglify: {
            options: {
                preserveComments: false
            },
            jsfiles: {
                files: [{
                    expand: true,
                    cwd: '<%= dirs.js %>/',
                    src: [
                        '*.js',
                        '!*.min.js',
                        '!Gruntfile.js',
                    ],
                    dest: '<%= dirs.js %>/',
                    ext: '.min.js'
                }]
            }
        },

        // Watch changes for assets
        watch: {
            sass: {
                files: [
                    '<%= dirs.scss %>/*.scss',
                ],
                tasks: ['sass'],
            },
            js: {
                files: [
                    '<%= dirs.js %>/*js',
                    '!<%= dirs.js %>/*.min.js'
                ],
                tasks: ['uglify']
            }
        },

        // generate languages pot file for translation
        makepot: {
            target: {
                options: {
                    cwd: '', // Directory of files to internationalize.
                    domainPath: 'lang/', // Where to save the POT file.
                    exclude: [], // List of files or directories to ignore.
                    include: [], // List of files or directories to include.
                    mainFile: '', // Main project file.
                    potComments: '', // The copyright at the beginning of the POT file.
                    potFilename: 'wp-best-courses-lbgs.pot', // Name of the POT file.
                    potHeaders: {
                        poedit: true, // Includes common Poedit headers.
                        'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
                    }, // Headers to add to the generated POT file.
                    processPot: null, // A callback function for manipulating the POT file.
                    type: 'wp-plugin', // Type of project (wp-plugin or wp-theme).
                    updateTimestamp: true, // Whether the POT-Creation-Date should be updated without other changes.
                    updatePoFiles: false // Whether to update PO files in the same directory as the POT file.
                }
            }
        },

        // Unit tests
        phpunit: {
            classes: {
                dir: 'tests/php/'
            },
            options: {
                bin: 'vendor/bin/phpunit',
                bootstrap: 'vendor/autoload.php',
                colors: true
            }
        },

        // Git hooks
        githooks: {
            options: {
                // Task-specific options go here.
            },
            all: {
                options: {
                    // Target-specific options go here
                },
                // Hook definitions go there (space-separated)
                'pre-commit': 'phpunit',
            }
        },
    });

    // Load NPM tasks to be used here
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-wp-i18n');
    grunt.loadNpmTasks('grunt-cssnano');
    grunt.loadNpmTasks('grunt-phpunit');
    grunt.loadNpmTasks('grunt-githooks');

    // Register tasks
    grunt.registerTask('default', [
        'sass',
        'cssnano',
        'cssmin',
        'uglify'
    ]);

};
