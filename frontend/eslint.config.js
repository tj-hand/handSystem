import eslintPluginVue from 'eslint-plugin-vue';
import eslintPluginPrettier from 'eslint-plugin-prettier';

export default [
	{
		plugins: {
			vue: eslintPluginVue,
			prettier: eslintPluginPrettier,
		},
		rules: {
			'no-unused-vars': ['warn', { vars: 'all', args: 'after-used', ignoreRestSiblings: false }],

			'vue/no-unused-vars': ['warn'], // Warn about unused variables inside Vue files
			'vue/no-unused-components': 'warn', // Warn about unused components
			'vue/multi-word-component-names': 'off', // Disable the multi-word component name rule
			'vue/require-default-prop': 'off', // Example of an essential Vue rule
			'vue/require-prop-types': 'warn', // Example: require prop types for better validation
			'vue/valid-v-for': 'warn', // Validates v-for directive
			'vue/valid-v-bind': 'warn', // Validates v-bind directive
			'vue/valid-v-on': 'warn', // Validates v-on directive
			'vue/valid-v-slot': 'warn', // Validates v-slot directive
			'volar.diagnostics.enable': true,
			'volar.inlayHints.enable': true,
			'prettier/prettier': ['error', { singleQuote: true, semi: true }],
		},
	},
	{
		rules: {
			'prettier/prettier': ['error', { singleQuote: true, semi: true }],
		},
	},
];
