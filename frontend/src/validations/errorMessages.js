/**
 * errorMessages.js
 * Handles error message formatting with i18n
 */

/**
 * Standard error message generators
 */
export const errorMessages = {
	required: (i18n, { label }) => i18n('validation.required', { label }),
	email: (i18n, { label }) => i18n('validation.invalid_email', { label }),
	min: (i18n, { label, parameter }) => i18n('validation.min_required', { label, parameter }),
	max: (i18n, { label, parameter }) => i18n('validation.max_exceeded', { label, parameter }),
	cnpj: (i18n, { label }) => i18n('validation.invalid_CNPJ', { label }),
	password: (i18n, { label }) => i18n('validation.invalid_password', { label }),
	password_confirmation: (i18n) => i18n('validation.confirm'),
};
