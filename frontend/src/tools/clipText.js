export function clipText(str, maxLength) {
	if (!str) return;
	if (str.length > maxLength) {
		return str.slice(0, maxLength) + '...';
	}
	return str;
}
