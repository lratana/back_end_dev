
export const BOOKING_TIME_ZONE = "Asia/Phnom_Penh";

/*
|--------------------------------------------------------------------------
| BOOKING TIME RULE
|--------------------------------------------------------------------------
| start_datetime / end_datetime = Cambodia local booking time.
|
| Example backend value:
|   2026-06-09 10:00:00
|
| Meaning:
|   Jun 9, 2026, 10:00 AM Cambodia time
|
| Do NOT add Z.
| Do NOT treat booking start/end as UTC.
|--------------------------------------------------------------------------
*/


export function parseBookingLocalDatetime(value) {
    if (!value) return null;

    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : value;
    }

    let text = String(value).trim().replace(" ", "T");

    if (text.length === 16) {
        text += ":00";
    }

    const hasTimezone =
        /Z$/i.test(text) ||
        /[+-]\d{2}:?\d{2}$/.test(text);

    // API datetime without timezone is treated as UTC.
    if (!hasTimezone) {
        text = `${text.slice(0, 19)}Z`;
    }

    const date = new Date(text);

    return Number.isNaN(date.getTime()) ? null : date;
}


/*
|--------------------------------------------------------------------------
| SYSTEM TIMESTAMP RULE
|--------------------------------------------------------------------------
| created_at / updated_at = Laravel/system timestamp.
| Usually UTC or server timestamp.
|--------------------------------------------------------------------------
*/

export function parseSystemTimestamp(value) {
    if (!value) return null;

    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : value;
    }

    let text = String(value).trim().replace(" ", "T");

    const hasTimezone =
        /Z$/i.test(text) ||
        /[+-]\d{2}:\d{2}$/.test(text);

    if (!hasTimezone) {
        text += "Z";
    }

    const date = new Date(text);

    return Number.isNaN(date.getTime()) ? null : date;
}

/*
|--------------------------------------------------------------------------
| FORM DATETIME RULE
|--------------------------------------------------------------------------
| HTML datetime-local has no timezone.
| Example:
|   2026-06-09T10:00
| means Cambodia 10:00 AM.
|--------------------------------------------------------------------------
*/

export function parseCambodiaFormDatetime(value) {
    if (!value) return null;

    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : value;
    }

    let text = String(value).trim().replace(" ", "T");

    if (text.length === 16) {
        text += ":00";
    }

    const date = new Date(`${text.slice(0, 19)}+07:00`);

    return Number.isNaN(date.getTime()) ? null : date;
}

export function formatBookingDate(value) {
    const date = parseBookingLocalDatetime(value);

    if (!date) return "-";

    return new Intl.DateTimeFormat("en-US", {
        timeZone: BOOKING_TIME_ZONE,
        year: "numeric",
        month: "short",
        day: "numeric",
    }).format(date);
}

export function formatBookingTime(value) {
    const date = parseBookingLocalDatetime(value);

    if (!date) return "-";

    return new Intl.DateTimeFormat("en-US", {
        timeZone: BOOKING_TIME_ZONE,
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    }).format(date);
}



export function formatBookingDateTime(value) {
    const date = parseBookingLocalDatetime(value);

    if (!date) return "-";

    return new Intl.DateTimeFormat("en-US", {
        timeZone: "Asia/Phnom_Penh",
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    }).format(date);
}



export function formatSystemDateTime(value) {
    const date = parseSystemTimestamp(value);

    if (!date) return "-";

    return new Intl.DateTimeFormat("en-US", {
        timeZone: BOOKING_TIME_ZONE,
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
        hour12: true,
    }).format(date);
}

export function formatDateOnly(value) {
    if (!value) return "-";

    const dateOnly = String(value).slice(0, 10);
    const date = new Date(`${dateOnly}T00:00:00+07:00`);

    if (Number.isNaN(date.getTime())) return "-";

    return new Intl.DateTimeFormat("en-US", {
        timeZone: BOOKING_TIME_ZONE,
        year: "numeric",
        month: "short",
        day: "numeric",
    }).format(date);
}

export function toDatetimeLocalInput(value) {
    if (!value) return "";

    if (value instanceof Date) {
        const yyyy = value.getFullYear();
        const mm = String(value.getMonth() + 1).padStart(2, "0");
        const dd = String(value.getDate()).padStart(2, "0");
        const hh = String(value.getHours()).padStart(2, "0");
        const ii = String(value.getMinutes()).padStart(2, "0");

        return `${yyyy}-${mm}-${dd}T${hh}:${ii}`;
    }

    const text = String(value).trim().replace(" ", "T");

    return text.slice(0, 16);
}

export function toMysqlDatetime(value) {
    if (!value) return null;

    if (value instanceof Date) {
        const localInput = toDatetimeLocalInput(value);

        return localInput ? `${localInput.replace("T", " ")}:00` : null;
    }

    let text = String(value).trim().replace("T", " ");

    if (text.length === 16) {
        text += ":00";
    }

    return text.slice(0, 19);
}

export function isPastBookingEnd(endDatetime) {
    const end = parseBookingLocalDatetime(endDatetime);

    if (!end) return false;

    return end.getTime() < Date.now();
}

export function isPastCambodiaFormEnd(endDatetime) {
    const end = parseCambodiaFormDatetime(endDatetime);

    if (!end) return false;

    return end.getTime() < Date.now();
}