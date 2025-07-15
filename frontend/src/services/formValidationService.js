/**
 * formValidationService.js
 * Main entry point for the validation service (backward compatibility)
 */

import { validators } from '@/validations/validators';
import { FormValidator } from '@/validations/FormValidator';
import { errorMessages } from '@/validations/errorMessages';
import { ValidationRegistry } from '@/validations/ValidationRegistry';

/**
 * Validates form data (legacy interface for backward compatibility)
 * @param {Object} formBuild - Object containing form field definitions with validation rules
 * @param {Object} record - Object containing the form data to validate
 * @param {Object} i18n - The i18n instance to use for translations
 * @returns {Object} - { valid: boolean, formWithErrors: Object }
 */
export function validateForm(formBuild, record, i18n) {
	const registry = new ValidationRegistry(validators, errorMessages);
	const validator = new FormValidator(i18n, registry);
	return validator.validate(formBuild, record);
}
