module.exports = function(grunt) {

	grunt.initConfig({

		copy: {
			main: {
				src: [
					'includes/**',
					'languages/*',
					'CHANGELOG.md',
					'LICENSE.txt',
					'readme.txt',
					'revision-strike.php'
				],
				dest: 'dist/'
			},
		},

		makepot: {
			target: {
				options: {
					domainPath: 'languages/',
					exclude: [
						'bin/*',
						'dist/*',
						'features/*',
						'node_modules/*',
						'plugin-repo-assets/*',
						'tests/*',
						'vendor/*'
					],
					mainFile: 'revision-strike.php',
					type: 'wp-plugin',
					updateTimestamp: false,
					updatePoFiles: true
				}
			}
	}
	});

	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-wp-i18n');

	grunt.registerTask('i18n', ['makepot']);
	grunt.registerTask('build', ['i18n', 'copy']);
};
