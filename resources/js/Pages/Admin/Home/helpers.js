// Shared helpers for the role dashboards on the admin home page.

export const axisColor = '#9ca3af'

export function formatAmount(n) {
    return '₦' + Number(n).toLocaleString('en-NG', { minimumFractionDigits: 0 })
}

export function formatDate(d) {
    return d ? new Date(d).toLocaleDateString('en-NG', { day: 'numeric', month: 'short' }) : null
}

// Compact "how long in this state" label from an ISO timestamp.
export function ago(iso) {
    if (!iso) return null
    const mins = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 60000))
    if (mins < 60) return `${mins}m`
    const hrs = Math.floor(mins / 60)
    if (hrs < 24) return `${hrs}h ${mins % 60}m`
    return `${Math.floor(hrs / 24)}d`
}

export function arrivesLabel(iso) {
    if (!iso) return null
    const d = new Date(iso), today = new Date()
    const diff = Math.round((d.setHours(0, 0, 0, 0) - today.setHours(0, 0, 0, 0)) / 86400000)
    if (diff <= 0) return 'arrives today'
    if (diff === 1) return 'arrives tomorrow'
    return `arrives in ${diff}d`
}
