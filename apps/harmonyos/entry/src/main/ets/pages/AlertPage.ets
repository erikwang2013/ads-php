// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
import router from '@ohos.router';
import { AlertRule } from '../model/AlertRule';

@Entry
@Component
struct AlertPage {
  @State alerts: AlertRule[] = [];
  @State isLoading: boolean = false;

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
      if (this.alerts.length === 0) {
        Column() {
          Text('No alert rules configured')
            .fontSize(16)
            .fontColor('#999999')
          Text('Create alerts to monitor your campaign performance')
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

        Toggle({ type: ToggleType.Switch, isOn: alert.status === 'enabled' })
          .selectedColor('#1677ff')
      }
      .width('100%')

      Row() {
        Text(`Metric: ${alert.metric}`)
          .fontSize(13)
          .fontColor('#666666')
          .margin({ right: 16 })

        Text(`Threshold: ${alert.operator} ${alert.threshold}`)
          .fontSize(13)
          .fontColor('#666666')
      }
      .width('100%')
      .margin({ top: 8 })

      Row() {
        Text(`Campaign: ${alert.campaignName}`)
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
      color: this.getStatusLeftBorderColor(alert.status)
    })
  }

  private getStatusColor(status: string): string {
    return status === 'enabled' ? '#52c41a' : '#d9d9d9';
  }

  private getStatusLeftBorderColor(status: string): string {
    return status === 'enabled' ? '#52c41a' : '#d9d9d9';
  }

  private loadAlerts() {
    this.isLoading = true;

    // Simulate data loading — replace with ApiClient.get('/alerts')
    setTimeout(() => {
      this.alerts = [
        { id: '1', name: 'High CPA on Summer Sale', metric: 'cpa', operator: '>', threshold: 50, status: 'enabled', campaignName: 'Summer Sale 2026' },
        { id: '2', name: 'Low CTR Warning', metric: 'ctr', operator: '<', threshold: 1.5, status: 'enabled', campaignName: 'All Campaigns' },
        { id: '3', name: 'Daily Budget Alert', metric: 'spend', operator: '>', threshold: 1000, status: 'disabled', campaignName: 'App Install Drive' }
      ];
      this.isLoading = false;
    }, 600);
  }
}
