/**
 * index.js
 * Main entry point for the validation module
 */

// Core classes
export { FormValidator } from '@/validations/FormValidator';
export { ValidationRegistry } from '@/validations/ValidationRegistry';

// Standard validators and error messages
export { validators } from '@/validations/validators';
export { errorMessages } from '@/validations/errorMessages';

// Legacy interface
export { validateForm } from '@/validations/formValidationService';

// Factory function for easy creation
import { validators } from '@/validations/validators';
import { errorMessages } from '@/validations/errorMessages';
import { FormValidator } from '@/validations/FormValidator';
import { ValidationRegistry } from '@/validations/ValidationRegistry';

/**
 * Creates a new validator instance with default validators
 * @param {Function} i18n - Translation function
 * @returns {FormValidator} New validator instance
 */
export function createValidator(i18n) {
	const registry = new ValidationRegistry(validators, errorMessages);
	return new FormValidator(i18n, registry);
}
