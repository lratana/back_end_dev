import dayjs from "dayjs";
import utc from "dayjs/plugin/utc";
import timezone from "dayjs/plugin/timezone";
import relativeTime from "dayjs/plugin/relativeTime";

dayjs.extend(utc);
dayjs.extend(timezone);
dayjs.extend(relativeTime);

const APP_TIMEZONE = "Asia/Phnom_Penh";

/**
 * IMPORTANT:
 * DB time is already stored as local Cambodia time
 * Example: 2026-03-27 11:02:00
 * So DO NOT use dayjs.utc()
 */
function parseAppTime(dateTime) {
  if (!dateTime) return null;
  return dayjs.tz(dateTime, "YYYY-MM-DD HH:mm:ss", APP_TIMEZONE);
}

export function formatChatTime(dateTime) {
  if (!dateTime) return "";

  const local = parseAppTime(dateTime);
  const now = dayjs().tz(APP_TIMEZONE);

  if (!local || !local.isValid()) return "";

  if (local.isSame(now, "day")) {
    return local.format("h:mm A");
  }

  if (local.isSame(now.subtract(1, "day"), "day")) {
    return "Yesterday";
  }

  if (local.isAfter(now.subtract(7, "day"))) {
    return local.format("ddd");
  }

  return local.format("MMM D");
}

export function formatFullDateTime(dateTime) {
  if (!dateTime) return "";
  const local = parseAppTime(dateTime);
  return local && local.isValid() ? local.format("MMM D, YYYY h:mm A") : "";
}

export function formatDateOnly(dateTime) {
  if (!dateTime) return "";
  const local = parseAppTime(dateTime);
  return local && local.isValid() ? local.format("MMM D, YYYY") : "";
}

export function formatTimeOnly(dateTime) {
  if (!dateTime) return "";
  const local = parseAppTime(dateTime);
  return local && local.isValid() ? local.format("h:mm A") : "";
}

export function getRelativeTime(dateTime) {
  if (!dateTime) return "";
  const local = parseAppTime(dateTime);
  return local && local.isValid() ? local.from(dayjs().tz(APP_TIMEZONE)) : "";
}

export function getCurrentTimezone() {
  return APP_TIMEZONE;
}