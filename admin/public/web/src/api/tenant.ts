import { api } from './index'

export interface TenantQuota {
  plan: string
  limits: {
    account_limit: number
    campaign_limit: number
    sync_daily: number
  }
  usage: {
    accounts: number
    campaigns: number
    sync_today: number
  }
}

export const tenantApi = {
  /** 当前租户套餐配额与用量 */
  getQuota() {
    return api.get<TenantQuota>('/tenant/quota')
  },
}
