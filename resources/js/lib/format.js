/** Parse "YYYY-MM-DD" as a local date (new Date('2026-09-30') would be read as UTC). */
export function parseDay(day) {
    const [y, m, d] = day.split('-').map(Number);
    return new Date(y, m - 1, d);
}

const dayFormat = new Intl.DateTimeFormat('en', { weekday: 'short', month: 'short', day: 'numeric' });
const shortFormat = new Intl.DateTimeFormat('en', { month: 'short', day: 'numeric' });
const longFormat = new Intl.DateTimeFormat('en', { weekday: 'long', month: 'long', day: 'numeric' });
const monthFormat = new Intl.DateTimeFormat('en', { month: 'short' });

export function startOfToday() {
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth(), now.getDate());
}

/** "Today", "Yesterday", "Tomorrow" or "Mon, Sep 28". */
export function dayLabel(day) {
    if (!day) return 'Bonus';
    const date = parseDay(day);
    const diff = Math.round((date - startOfToday()) / 86400000);
    if (diff === 0) return 'Today';
    if (diff === -1) return 'Yesterday';
    if (diff === 1) return 'Tomorrow';
    return dayFormat.format(date);
}

export const shortDay = (day) => shortFormat.format(parseDay(day));
export const longDay = (day) => longFormat.format(parseDay(day));
export const monthName = (day) => monthFormat.format(parseDay(day));

export function timeAgo(iso) {
    const seconds = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 1000));
    if (seconds < 60) return 'just now';
    const minutes = Math.round(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.round(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.round(hours / 24);
    if (days < 7) return `${days}d ago`;
    return shortFormat.format(new Date(iso));
}

export const formatNumber = (value) => Number(value ?? 0).toLocaleString('en');

export function greeting() {
    const hour = new Date().getHours();
    if (hour < 5) return 'Burning the midnight oil';
    if (hour < 12) return 'Good morning';
    if (hour < 17) return 'Good afternoon';
    return 'Good evening';
}

export const plural = (count, word, many = `${word}s`) => `${formatNumber(count)} ${count === 1 ? word : many}`;
