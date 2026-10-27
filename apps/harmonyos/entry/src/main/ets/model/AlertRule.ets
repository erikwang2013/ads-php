// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
export class AlertRule {
  id: string = '';
  name: string = '';
  metric: string = '';
  operator: string = '';
  threshold: number = 0;
  status: string = '';
  campaignName: string = '';
  // AlertLog 字段（/api/alerts/logs）：无 level/title/content，
  // 内容由 metric/current_value/condition/threshold 拼装
  currentValue: number = 0;
  condition: string = '';
  createdAt: string = '';
}
