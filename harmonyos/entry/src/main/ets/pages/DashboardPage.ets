// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
import router from '@ohos.router';
import { formatFen, formatNumber } from '../utils/FormatUtil';

@Entry
@Component
struct DashboardPage {
  @State totalCost: number = 1250000;      // in fen
  @State impressions: number = 458200;
  @State clicks: number = 12340;
  @State ctr: number = 2.69;

  build() {
    Column() {
      // Top navigation bar
      Row() {
        Text('Dashboard')
          .fontSize(20)
          .fontWeight(FontWeight.Bold)
          .fontColor('#1a1a1a')

        Blank()

        Row() {
          // Navigation icons as text buttons
          Text('Campaigns')
            .fontSize(14)
            .fontColor('#1677ff')
            .onClick(() => {
              router.pushUrl({ url: 'pages/CampaignListPage' });
            })
            .margin({ right: 16 })

          Text('Accounts')
            .fontSize(14)
            .fontColor('#1677ff')
            .onClick(() => {
              router.pushUrl({ url: 'pages/AccountPage' });
            })
            .margin({ right: 16 })

          Text('Reports')
            .fontSize(14)
            .fontColor('#1677ff')
            .onClick(() => {
              router.pushUrl({ url: 'pages/ReportPage' });
            })
            .margin({ right: 16 })

          Text('Alerts')
            .fontSize(14)
            .fontColor('#1677ff')
            .onClick(() => {
              router.pushUrl({ url: 'pages/AlertPage' });
            })
        }
      }
      .width('100%')
      .padding({ left: 20, right: 20, top: 16, bottom: 16 })
      .backgroundColor('#ffffff')

      // Scrollable content
      Scroll() {
        Column() {
          // Metric cards grid
          Text('Overview')
            .fontSize(16)
            .fontWeight(FontWeight.Medium)
            .fontColor('#333333')
            .width('100%')
            .padding({ left: 20, top: 20, bottom: 12 })

          Grid() {
            GridItem() {
              this.MetricCard('Total Cost', `¥${formatFen(this.totalCost)}`)
            }
            GridItem() {
              this.MetricCard('Impressions', formatNumber(this.impressions))
            }
            GridItem() {
              this.MetricCard('Clicks', formatNumber(this.clicks))
            }
            GridItem() {
              this.MetricCard('CTR', `${this.ctr.toFixed(2)}%`)
            }
          }
          .columnsTemplate('1fr 1fr')
          .rowsGap(12)
          .columnsGap(12)
          .padding({ left: 20, right: 20 })
          .width('100%')

          // Chart placeholder section
          Text('Performance Trend')
            .fontSize(16)
            .fontWeight(FontWeight.Medium)
            .fontColor('#333333')
            .width('100%')
            .padding({ left: 20, top: 24, bottom: 12 })

          Column() {
            Text('Chart Area')
              .fontSize(14)
              .fontColor('#999999')
            Text('Integrate @ohos/echarts or custom Canvas chart here')
              .fontSize(12)
              .fontColor('#cccccc')
              .margin({ top: 8 })
          }
          .width('100%')
          .height(200)
          .justifyContent(FlexAlign.Center)
          .alignItems(HorizontalAlign.Center)
          .backgroundColor('#fafafa')
          .borderRadius(8)
          .margin({ left: 20, right: 20 })

          // Quick links
          Text('Quick Actions')
            .fontSize(16)
            .fontWeight(FontWeight.Medium)
            .fontColor('#333333')
            .width('100%')
            .padding({ left: 20, top: 24, bottom: 12 })

          Row() {
            this.QuickActionButton('Campaigns', '#1677ff', () => {
              router.pushUrl({ url: 'pages/CampaignListPage' });
            })
            this.QuickActionButton('Accounts', '#52c41a', () => {
              router.pushUrl({ url: 'pages/AccountPage' });
            })
            this.QuickActionButton('Reports', '#fa8c16', () => {
              router.pushUrl({ url: 'pages/ReportPage' });
            })
            this.QuickActionButton('Alerts', '#f5222d', () => {
              router.pushUrl({ url: 'pages/AlertPage' });
            })
          }
          .width('100%')
          .padding({ left: 20, right: 20 })
          .justifyContent(FlexAlign.SpaceBetween)

          Blank()
        }
        .width('100%')
      }
      .layoutWeight(1)
      .scrollBar(BarState.Off)

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
  MetricCard(label: string, value: string) {
    Column() {
      Text(label)
        .fontSize(12)
        .fontColor('#888888')
        .margin({ bottom: 8 })
      Text(value)
        .fontSize(22)
        .fontWeight(FontWeight.Bold)
        .fontColor('#1a1a1a')
    }
    .width('100%')
    .padding({ top: 16, bottom: 16, left: 16, right: 16 })
    .backgroundColor('#ffffff')
    .borderRadius(8)
    .alignItems(HorizontalAlign.Start)
  }

  @Builder
  QuickActionButton(label: string, color: string, onClick: () => void) {
    Column() {
      Text(label)
        .fontSize(13)
        .fontColor(color)
        .fontWeight(FontWeight.Medium)
    }
    .width(78)
    .height(40)
    .justifyContent(FlexAlign.Center)
    .alignItems(HorizontalAlign.Center)
    .backgroundColor('#ffffff')
    .borderRadius(8)
    .border({ width: 1, color: color })
    .onClick(onClick)
  }
}
