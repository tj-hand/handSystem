/**
 * validators.js
 * Contains pure validation functions
 */

/**
 * Standard validation functions
 */
export const validators = {
	/**
	 * Checks if a value is not empty
	 */
	required: (value) => {
		return value !== undefined && value !== null && String(value).trim() !== '';
	},

	/**
	 * Validates email format
	 */
	email: (value) => {
		if (!value) return true;
		const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return emailRegex.test(value);
	},

	/**
	 * Checks if value meets minimum length
	 */
	min: (value, parameter) => {
		const minLength = parseInt(parameter, 10);
		return !value || String(value).length >= minLength;
	},

	/**
	 * Checks if value doesn't exceed maximum length
	 */
	max: (value, parameter) => {
		const maxLength = parseInt(parameter, 10);
		return !value || String(value).length <= maxLength;
	},

	/**
	 * Validates CNPJ format (Brazilian company ID)
	 */
	cnpj: (value) => {
		if (!value) return true;
		const cnpjRegex = /^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/;
		return cnpjRegex.test(value);
	},

	/**
	 * Validates password strength
	 */
	password: (value, parameter, context) => {
		// Store password for confirmation checks
		context.passwordCache = value || '';

		// Allow empty password if parameter is 'null'
		const allowEmptyPassword = parameter === 'null';
		if (allowEmptyPassword && (!value || value === '')) {
			return true;
		}

		return (
			value?.length >= 8 &&
			/[A-Z]/.test(value) &&
			/[a-z]/.test(value) &&
			/\d/.test(value) &&
			/[-!@$%^&*()_+|~=`{}\[\]:";'<>?,.\/]/.test(value)
		);
	},

	/**
	 * Checks if value matches stored password
	 */
	password_confirmation: (value, _, context) => {
		return value === context.passwordCache;
	},
};
