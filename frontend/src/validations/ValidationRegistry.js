/**
 * ValidationRegistry.js
 * Manages validation rules and allows for custom validators
 */

export class ValidationRegistry {
	/**
	 * Create a new validation registry
	 * @param {Object} defaultValidators - Initial set of validator functions
	 * @param {Object} defaultMessages - Initial set of error message functions
	 */
	constructor(defaultValidators = {}, defaultMessages = {}) {
		this.validators = { ...defaultValidators };
		this.errorMessages = { ...defaultMessages };
	}

	/**
	 * Add a custom validator
	 * @param {string} name - Name of the validator
	 * @param {Function} validatorFn - Validation function
	 * @param {Function} messageFn - Error message function
	 * @returns {ValidationRegistry} This registry for chaining
	 */
	addValidator(name, validatorFn, messageFn) {
		this.validators[name] = validatorFn;
		this.errorMessages[name] = messageFn;
		return this;
	}

	/**
	 * Add multiple validators at once
	 * @param {Object} validators - Object with validator functions
	 * @param {Object} messages - Object with error message functions
	 * @returns {ValidationRegistry} This registry for chaining
	 */
	addValidators(validators = {}, messages = {}) {
		Object.entries(validators).forEach(([name, fn]) => {
			this.validators[name] = fn;
		});

		Object.entries(messages).forEach(([name, fn]) => {
			this.errorMessages[name] = fn;
		});

		return this;
	}

	/**
	 * Get a validator by name
	 * @param {string} name - Name of the validator
	 * @returns {Function|null} Validator function or null if not found
	 */
	getValidator(name) {
		return this.validators[name] || null;
	}

	/**
	 * Get an error message builder by name
	 * @param {string} name - Name of the error message
	 * @returns {Function|null} Error message function or null if not found
	 */
	getErrorMessage(name) {
		return this.errorMessages[name] || null;
	}
}
