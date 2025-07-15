/**
 * FormValidator.js
 * Main validation class that orchestrates the validation process
 * with support for recursive form structures
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
	 * @param {Object} record - Data to validate
	 * @returns {Object} Validation result with errors
	 */
	validate(formBuild, record) {
		const errors = {};
		const formValues = record;
		const formWithErrors = JSON.parse(JSON.stringify(formBuild));

		// Reset validation context
		this.context = { passwordCache: '' };

		// Process form structure recursively
		this._validateFormStructure(formWithErrors, formValues, errors);

		return {
			valid: Object.keys(errors).length === 0,
			formWithErrors,
		};
	}

	/**
	 * Recursively validate form structure
	 * @param {Array|Object} formElement - Form element to validate (can be array of rows or a single element)
	 * @param {Object} formValues - Form values
	 * @param {Object} errors - Accumulator for validation errors
	 * @private
	 */
	_validateFormStructure(formElement, formValues, errors) {
		// Handle array of elements (top-level form or sub-arrays)
		if (Array.isArray(formElement)) {
			formElement.forEach((element) => {
				this._validateFormStructure(element, formValues, errors);
			});
			return;
		}

		// Handle row type
		if (formElement.type === 'row' && Array.isArray(formElement.cols)) {
			formElement.cols.forEach((col) => {
				this._validateFormStructure(col, formValues, errors);
			});
			return;
		}

		// Handle column with content
		if (formElement.content) {
			this._validateFormStructure(formElement.content, formValues, errors);
			return;
		}

		// Handle field type
		if (formElement.type === 'field') {
			this._validateField(formElement.props, formValues, errors);
			return;
		}

		// Handle nested formBuild (recursive form structures)
		if (formElement.type === 'nestedForm' && formElement.formBuild) {
			// For nested forms, we can either:
			// 1. Validate with the same formValues (flat structure)
			// 2. Or use a nested object if your record has nested structure
			const nestedrecord = formElement.dataPath
				? this._getNestedValue(formValues, formElement.dataPath)
				: formValues;

			this._validateFormStructure(formElement.formBuild, nestedrecord || {}, errors);
			return;
		}

		// Handle group elements that may contain fields
		if (formElement.children && Array.isArray(formElement.children)) {
			formElement.children.forEach((child) => {
				this._validateFormStructure(child, formValues, errors);
			});
			return;
		}

		// Handle any other custom container types with fields
		if (formElement.fields && Array.isArray(formElement.fields)) {
			formElement.fields.forEach((field) => {
				this._validateFormStructure(field, formValues, errors);
			});
			return;
		}
	}

	/**
	 * Validate a single field
	 * @param {Object} field - Field properties including validations
	 * @param {Object} formValues - All form values
	 * @param {Object} errors - Accumulator for validation errors
	 * @private
	 */
	_validateField(field, formValues, errors) {
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
	}

	/**
	 * Get a nested value from an object using dot notation path
	 * @param {Object} obj - Object to get value from
	 * @param {string} path - Path to the value using dot notation (e.g. 'user.address.street')
	 * @returns {any} The value at the specified path or undefined if not found
	 * @private
	 */
	_getNestedValue(obj, path) {
		if (!obj || !path) return undefined;

		const parts = path.split('.');
		let current = obj;

		for (const part of parts) {
			if (current === null || current === undefined) return undefined;
			current = current[part];
		}

		return current;
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
