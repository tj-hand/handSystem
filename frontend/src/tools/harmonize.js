// harmonize.js

/**
 * Finds the closest golden ratio-based width to a reference width
 * @param {number} screenWidth - The current screen or container width
 * @param {number} referenceWidth - The width you want to find the closest golden ratio match for
 * @return {number} The closest golden ratio-based width, rounded to the nearest integer
 */
export function closest(screenWidth, referenceWidth) {
	const phi = 1.618;
	const inversePhi = 0.618;

	let closestWidth = screenWidth;
	let smallestDifference = Math.abs(screenWidth - referenceWidth);

	// Check powers of phi and inverse phi to find the closest value
	for (let i = -10; i <= 10; i++) {
		// Calculate width based on phi^i
		const multiplier = i >= 0 ? Math.pow(phi, i) : Math.pow(inversePhi, Math.abs(i));
		const goldenWidth = screenWidth * multiplier;
		const difference = Math.abs(goldenWidth - referenceWidth);

		// Update if this is closer than our current closest
		if (difference < smallestDifference) {
			smallestDifference = difference;
			closestWidth = goldenWidth;
		}
	}

	return Math.round(closestWidth);
}

/**
 * Get a list of all golden ratio-based widths from the screen width
 * @param {number} screenWidth - The current screen or container width
 * @return {Array<number>} Array of golden ratio-based widths
 */
export function all(screenWidth) {
	const phi = 1.618;
	const inversePhi = 0.618;
	const widths = [];

	for (let i = -10; i <= 10; i++) {
		const multiplier = i >= 0 ? Math.pow(phi, i) : Math.pow(inversePhi, Math.abs(i));
		widths.push(Math.round(screenWidth * multiplier));
	}

	return widths.sort((a, b) => a - b);
}
