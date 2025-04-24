/**
 * FormValidator.js
 * Main validation class that orchestrates the validation process
 */

export class FormValidator {
	/**
	 * Create a new form validator
	 * @param {Function} i18n - Translation function
	 * @param {ValidationRegistry} registry - Registry of validators
	 */
	constructor(i18n, registry) {
		this.i18n = i18n;
		this.registry = registry;
		this.context = {}; // Shared context for interdependent validations
	}

	/**
	 * Validate a form against validation rules
	 * @param {Object} formBuild - Form structure with validation rules
	 * @param {Object} formData - Data to validate
	 * @returns {Object} Validation result with errors
	 */
	validate(formBuild, formData) {
		const errors = {};
		const formValues = formData;
		const formWithErrors = JSON.parse(JSON.stringify(formBuild));

		// Reset validation context
		this.context = { passwordCache: '' };

		// Process each row
		formWithErrors.forEach((row) => {
			if (row.type !== 'row' || !Array.isArray(row.cols)) return;

			row.cols.forEach((col) => {
				const content = col.content;
				if (!content || content.type !== 'field') return;

				const field = content.props;
				if (!field || !Array.isArray(field.validations)) return;

				const fieldName = field.db_name;
				const value = formValues[fieldName];
				const labelTranslation = this.i18n(field.label);
				let fieldErrors = [];

				// Apply each validation rule
				field.validations.forEach((validation) => {
					const [validationType, parameter] = validation.split(':');

					const validator = this.registry.getValidator(validationType);
					if (!validator) return;

					const isValid = validator(value, parameter, this.context);

					if (!isValid) {
						const getErrorMessage = this.registry.getErrorMessage(validationType);
						if (!getErrorMessage) return;

						const errorMessage = getErrorMessage(this.i18n, {
							label: labelTranslation,
							parameter,
						});

						fieldErrors.push(errorMessage);
					}
				});

				// Set errors for this field
				if (fieldErrors.length > 0) {
					field.error = fieldErrors;
					errors[fieldName] = true;
				} else {
					field.error = null;
				}
			});
		});

		return {
			valid: Object.keys(errors).length === 0,
			formWithErrors,
		};
	}

	/**
	 * Validate a single value against a specific rule
	 * @param {any} value - Value to validate
	 * @param {string} validationType - Type of validation to perform
	 * @param {string} parameter - Optional parameter for validation
	 * @returns {boolean} Whether the value is valid
	 */
	validateValue(value, validationType, parameter = null) {
		const validator = this.registry.getValidator(validationType);
		if (!validator) return true;

		return validator(value, parameter, this.context);
	}
}
