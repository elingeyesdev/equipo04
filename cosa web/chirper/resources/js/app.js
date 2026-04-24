import './bootstrap';

const POLL_INTERVAL_MS = 15000;
const LAST_REPORT_KEY = 'authority:last_seen_report_id';

function getMetaContent(name) {
	const element = document.querySelector(`meta[name="${name}"]`);

	return element ? element.getAttribute('content') ?? '' : '';
}

function parseReportId(reportId) {
	const numericId = Number.parseInt(String(reportId), 10);

	return Number.isNaN(numericId) ? null : numericId;
}

function getStoredLastSeenId() {
	const value = window.localStorage.getItem(LAST_REPORT_KEY);

	return value === null ? null : parseReportId(value);
}

function setStoredLastSeenId(reportId) {
	window.localStorage.setItem(LAST_REPORT_KEY, String(reportId));
}

async function fetchLatestReport(endpoint) {
	const response = await window.fetch(endpoint, {
		method: 'GET',
		headers: {
			Accept: 'application/json',
		},
		credentials: 'same-origin',
	});

	if (!response.ok) {
		return null;
	}

	const payload = await response.json();
	const data = payload?.data;

	if (!data || data.id === undefined || data.id === null) {
		return null;
	}

	const reportId = parseReportId(data.id);

	if (reportId === null) {
		return null;
	}

	return {
		id: reportId,
		severity: String(data.severity ?? ''),
	};
}

function showNotificationForReport(report) {
	if (!('Notification' in window) || Notification.permission !== 'granted') {
		return;
	}

	const severity = report.severity.trim() === '' ? 'desconocida' : report.severity;

	new Notification(`nuevo reporte de amenaza ${severity}`);
}

async function startAuthorityNotifications(endpoint) {
	if (!('Notification' in window)) {
		console.warn('Desktop notifications are not supported by this browser.');
		return;
	}

	if (!window.isSecureContext) {
		console.warn('Desktop notifications require a secure context (https or localhost).');
		return;
	}

	if (Notification.permission === 'default') {
		try {
			await Notification.requestPermission();
		} catch (error) {
			console.warn('Could not request notification permission.', error);
			return;
		}
	}

	if (Notification.permission !== 'granted') {
		console.warn('Notification permission was not granted.');
		return;
	}

	let lastSeenId = getStoredLastSeenId();

	const checkForNewReports = async (notify) => {
		try {
			const latest = await fetchLatestReport(endpoint);

			if (latest === null) {
				return;
			}

			if (lastSeenId === null) {
				lastSeenId = latest.id;
				setStoredLastSeenId(latest.id);
				return;
			}

			if (latest.id > lastSeenId) {
				lastSeenId = latest.id;
				setStoredLastSeenId(latest.id);

				if (notify) {
					showNotificationForReport(latest);
				}
			}
		} catch {
			// Ignore transient errors to keep polling active.
		}
	};

	await checkForNewReports(false);
	window.setInterval(() => {
		checkForNewReports(true);
	}, POLL_INTERVAL_MS);
}

document.addEventListener('DOMContentLoaded', () => {
	const role = getMetaContent('api-user-role').trim().toLowerCase();
	const endpoint = getMetaContent('reports-notifications-endpoint');

	if (role !== 'authority' || endpoint === '') {
		return;
	}

	startAuthorityNotifications(endpoint);
});
