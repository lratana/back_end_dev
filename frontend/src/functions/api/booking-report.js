// @func/api/booking-report.js

export async function apiGetBookingReports(params = {}) {
    return await window.axios.get(`${window.API_URL}/booking-reports`, { params });
}

export async function apiGetDailyBookingReport(date = null, extra = {}) {
    return await window.axios.get(`${window.API_URL}/booking-reports`, {
        params: {
            type: "daily",
            date,
            ...extra,
        },
    });
}

export async function apiGetMonthlyBookingReport(month = null, year = null, extra = {}) {
    return await window.axios.get(`${window.API_URL}/booking-reports`, {
        params: {
            type: "monthly",
            month,
            year,
            ...extra,
        },
    });
}

export async function apiGetYearlyBookingReport(year = null, extra = {}) {
    return await window.axios.get(`${window.API_URL}/booking-reports`, {
        params: {
            type: "yearly",
            year,
            ...extra,
        },
    });
}