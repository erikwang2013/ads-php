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
