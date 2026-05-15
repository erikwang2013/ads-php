// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

/**
 * Convert fen (1/100 yuan) to yuan string with 2 decimal places.
 * e.g. 12500 -> '125.00'
 */
export function formatFen(fen: number): string {
  return (fen / 100).toFixed(2);
}

/**
 * Format large numbers with Chinese units.
 * 10000 -> '1.00万'
 * 100000000 -> '1.00亿'
 * Otherwise locale string with commas.
 */
export function formatNumber(n: number): string {
  if (n >= 100000000) {
    return (n / 100000000).toFixed(2) + '亿';
  }
  if (n >= 10000) {
    return (n / 10000).toFixed(2) + '万';
  }
  return n.toLocaleString();
}

/**
 * Format a percentage value (already in percent form).
 * e.g. 2.69123 -> '2.69%'
 */
export function formatPercent(p: number, decimals: number = 2): string {
  return p.toFixed(decimals) + '%';
}

/**
 * Format a date string like 'YYYY-MM-DD' to friendly display.
 */
export function formatDate(dateStr: string): string {
  if (!dateStr || dateStr.length < 10) return dateStr;
  let parts = dateStr.substring(0, 10).split('-');
  return `${parts[0]}年${parseInt(parts[1])}月${parseInt(parts[2])}日`;
}

/**
 * Format a duration in seconds to human-readable string.
 */
export function formatDuration(seconds: number): string {
  if (seconds < 60) return `${seconds}秒`;
  if (seconds < 3600) return `${Math.floor(seconds / 60)}分钟`;
  let hours = Math.floor(seconds / 3600);
  let mins = Math.floor((seconds % 3600) / 60);
  return `${hours}小时${mins > 0 ? mins + '分钟' : ''}`;
}
