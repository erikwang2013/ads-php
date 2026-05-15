// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
export class ReportMetric {
  campaignId: string = '';
  campaignName: string = '';
  date: string = '';
  cost: number = 0;         // in fen
  impressions: number = 0;
  clicks: number = 0;
  ctr: number = 0;
  cpm: number = 0;          // in fen
  cpc: number = 0;          // in fen
  conversions: number = 0;
}
