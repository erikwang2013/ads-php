import { api } from './index'

export interface SyncStatusSummary {
  total_accounts: number
  synced_24h: number
  error_7d: number
  pending_retries: number
}

export interface SyncAccountStatus {
  id: number
  account_name: string
  platform: string
  last_sync_at: string | null
  sync_errors_count: number
  pending_retries: number
}

export interface SyncStatusData {
  summary: SyncStatusSummary
  accounts: SyncAccountStatus[]
}

export interface SyncErrorItem {
  id: number
  platform_account_id: number
  account_name: string
  platform: string
  retry_count: number
  last_error: string
  next_retry_at: string | null
  created_at: string
}

export interface SyncErrorPagination {
  page: number
  per_page: number
  total: number
  total_pages: number
}

export interface SyncErrorListData {
  list: SyncErrorItem[]
  pagination: SyncErrorPagination
}

export const syncApi = {
  /** 同步状态概览：summary 摘要 + 账户维度状态列表 */
  status() {
    return api.get<SyncStatusData>('/sync/status')
  },
  /** 同步错误分页明细 */
  errors(params?: { page?: number; per_page?: number }) {
    return api.get<SyncErrorListData>('/sync/errors', { params })
  },
}
