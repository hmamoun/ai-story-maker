import wordpress from '@wordpress/eslint-plugin';
module.exports = {
	root: true,
	extends: [
		'plugin:@wordpress/eslint-plugin/recommended',
		'plugin:prettier/recommended' // Prettier plugin with recommended rules
	],
	plugins: ['prettier'],
	rules: {
		'prettier/prettier': 'error',
	},
};

export default [
	{
		files: ['**/*.js'],
		plugins: {
			'@wordpress': wordpress,
		},
		rules: {
			...wordpress.configs.recommended.rules,
		},
	},
];


