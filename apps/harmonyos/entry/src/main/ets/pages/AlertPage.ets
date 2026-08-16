// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
import router from '@ohos.router';
import { AlertRule } from '../model/AlertRule';
import { ApiClient } from '../api/ApiClient';
import { strVal, numVal } from '../utils/FormatUtil';

@Entry
@Component
struct AlertPage {
  @State alerts: AlertRule[] = [];
  @State isLoading: boolean = false;
  @State errorMessage: string = '';

  aboutToAppear() {
    this.loadAlerts();
  }

  build() {
    Column() {
      // Header
      Row() {
        Text('←')
          .fontSize(24)
          .fontColor('#1677ff')
          .onClick(() => {
            router.back();
          })

        Text('Alerts')
          .fontSize(20)
          .fontWeight(FontWeight.Bold)
          .fontColor('#1a1a1a')
          .margin({ left: 12 })

        Blank()

        Text('+ New Alert')
          .fontSize(14)
          .fontColor('#1677ff')
          .fontWeight(FontWeight.Medium)
      }
      .width('100%')
      .padding({ left: 20, right: 20, top: 16, bottom: 16 })
      .backgroundColor('#ffffff')

      // Alert list
      if (this.isLoading && this.alerts.length === 0) {
        Column() {
          Text('Loading...')
            .fontSize(16)
            .fontColor('#999999')
        }
        .width('100%')
        .layoutWeight(1)
        .justifyContent(FlexAlign.Center)
        .alignItems(HorizontalAlign.Center)
      } else if (this.errorMessage !== '' && this.alerts.length === 0) {
        Column() {
          Text('Failed to load alerts')
            .fontSize(16)
            .fontColor('#f5222d')
          Text(this.errorMessage)
            .fontSize(12)
            .fontColor('#999999')
            .margin({ top: 4 })
            .maxLines(2)
          Button('Retry')
            .fontSize(14)
            .fontColor('#1677ff')
            .backgroundColor('#ffffff')
            .border({ width: 1, color: '#1677ff' })
            .borderRadius(8)
            .margin({ top: 12 })
            .onClick(() => {
              this.loadAlerts();
            })
        }
        .width('100%')
        .layoutWeight(1)
        .justifyContent(FlexAlign.Center)
        .alignItems(HorizontalAlign.Center)
      } else if (this.alerts.length === 0) {
        Column() {
          Text('No alert logs yet')
            .fontSize(16)
            .fontColor('#999999')
          Text('Alerts will appear here when campaign metrics cross thresholds')
            .fontSize(13)
            .fontColor('#cccccc')
            .margin({ top: 8 })
        }
        .width('100%')
        .layoutWeight(1)
        .justifyContent(FlexAlign.Center)
        .alignItems(HorizontalAlign.Center)
      } else {
        List({ space: 12 }) {
          ForEach(this.alerts, (alert: AlertRule) => {
            ListItem() {
              this.AlertItem(alert)
            }
          }, (alert: AlertRule) => alert.id)
        }
        .width('100%')
        .layoutWeight(1)
        .padding({ left: 20, right: 20, top: 16, bottom: 16 })
      }

      // Copyright footer
      Text('Copyright (c) 2026 erik — https://erik.xyz')
        .fontSize(12)
        .fontColor('#aaaaaa')
        .padding({ top: 8, bottom: 16 })
        .width('100%')
        .textAlign(TextAlign.Center)
    }
    .width('100%')
    .height('100%')
    .backgroundColor('#f0f2f5')
  }

  @Builder
  AlertItem(alert: AlertRule) {
    Column() {
      Row() {
        // Status indicator dot
        Circle({ width: 10, height: 10 })
          .fill(this.getStatusColor(alert.status))

        Text(alert.name)
          .fontSize(16)
          .fontWeight(FontWeight.Medium)
          .fontColor('#1a1a1a')
          .margin({ left: 10 })
          .layoutWeight(1)

        Text(alert.status)
          .fontSize(12)
          .fontColor(this.getStatusColor(alert.status))
          .padding({ left: 8, right: 8, top: 2, bottom: 2 })
          .backgroundColor(this.getStatusBgColor(alert.status))
          .borderRadius(10)
      }
      .width('100%')

      // AlertLog 无 title/content：内容由 metric/current_value/condition/threshold 拼装（同 Flutter alert_page.dart）
      Row() {
        Text(`Metric: ${alert.metric}`)
          .fontSize(13)
          .fontColor('#666666')
          .margin({ right: 16 })

        Text(`Value: ${alert.currentValue} ${this.getConditionSymbol(alert.condition)} ${alert.threshold}`)
          .fontSize(13)
          .fontColor('#666666')
      }
      .width('100%')
      .margin({ top: 8 })

      Row() {
        Text(alert.createdAt.length > 16 ? alert.createdAt.substring(0, 16) : alert.createdAt)
          .fontSize(12)
          .fontColor('#999999')
          .margin({ top: 6 })
      }
      .width('100%')
    }
    .width('100%')
    .padding(16)
    .backgroundColor('#ffffff')
    .borderRadius(8)
    .border({
      width: { left: 3 },
      color: this.getStatusColor(alert.status)
    })
  }

  private getConditionSymbol(condition: string): string {
    const symbols: Record<string, string> = {
      'gt': '>',
      'gte': '≥',
      'lt': '<',
      'lte': '≤'
    };
    return symbols[condition] || '';
  }

  private getStatusColor(status: string): string {
    if (status === 'triggered') return '#f5222d';
    if (status === 'acknowledged') return '#1890ff';
    return '#999999'; // resolved
  }

  private getStatusBgColor(status: string): string {
    if (status === 'triggered') return '#fff1f0';
    if (status === 'acknowledged') return '#e6f7ff';
    return '#f5f5f5'; // resolved
  }

  /**
   * GET /alerts/logs → data.list（分页）→ AlertRule[]（承载 AlertLog 字段）。
   * service 字段：rule_name/metric/current_value/condition/threshold/status/created_at。
   */
  private async loadAlerts() {
    this.isLoading = true;
    this.errorMessage = '';
    try {
      let res = await ApiClient.get('/alerts/logs') as Record<string, Object>;
      let list = res['list'] as Object[];
      let items: AlertRule[] = [];
      if (list !== undefined) {
        for (let item of list) {
          let m = item as Record<string, Object>;
          let a = new AlertRule();
          a.id = String(m['id']);
          a.name = strVal(m['rule_name']);
          a.metric = strVal(m['metric']);
          a.currentValue = numVal(m['current_value']);
          a.threshold = numVal(m['threshold']);
          a.condition = strVal(m['condition']);
          a.status = strVal(m['status'], 'triggered');
          a.createdAt = strVal(m['created_at']);
          items.push(a);
        }
      }
      this.alerts = items;
      this.isLoading = false;
    } catch (e) {
      this.errorMessage = (e as Error).message;
      this.isLoading = false;
    }
  }
}
