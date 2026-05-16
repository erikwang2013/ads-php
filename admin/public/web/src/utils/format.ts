export function formatFen(fen: number): string {
  const yuan = fen / 100
  if (yuan >= 10000) return (yuan / 10000).toFixed(2) + '万'
  return yuan.toFixed(2)
}

export function formatNumber(n: number): string {
  if (n >= 100000000) return (n / 100000000).toFixed(2) + '亿'
  if (n >= 10000) return (n / 10000).toFixed(2) + '万'
  return n.toLocaleString()
}

export function formatPercent(n: number): string {
  return (n * 100).toFixed(2) + '%'
}

export function formatDate(d: Date): string {
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  const h = String(d.getHours()).padStart(2, '0')
  const min = String(d.getMinutes()).padStart(2, '0')
  const s = String(d.getSeconds()).padStart(2, '0')
  return `${y}${m}${day}${h}${min}${s}`
}
