// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
import router from '@ohos.router';
import { ReportMetric } from '../model/ReportMetric';
import { formatFen, formatNumber } from '../utils/FormatUtil';

@Entry
@Component
struct ReportPage {
  @State reports: ReportMetric[] = [];
  @State isLoading: boolean = false;
  @State selectedDate: string = this.getTodayStr();

  aboutToAppear() {
    this.loadReports();
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

        Text('Reports')
          .fontSize(20)
          .fontWeight(FontWeight.Bold)
          .fontColor('#1a1a1a')
          .margin({ left: 12 })

        Blank()
      }
      .width('100%')
      .padding({ left: 20, right: 20, top: 16, bottom: 16 })
      .backgroundColor('#ffffff')

      // Date selector
      Row() {
        Text('← Prev')
          .fontSize(14)
          .fontColor('#1677ff')
          .onClick(() => {
            this.shiftDate(-1);
          })

        Text(this.selectedDate)
          .fontSize(16)
          .fontWeight(FontWeight.Medium)
          .fontColor('#1a1a1a')
          .margin({ left: 16, right: 16 })

        Text('Next →')
          .fontSize(14)
          .fontColor('#1677ff')
          .onClick(() => {
            this.shiftDate(1);
          })
      }
      .width('100%')
      .justifyContent(FlexAlign.Center)
      .padding({ top: 16, bottom: 16 })
      .backgroundColor('#ffffff')

      // Summary cards row
      if (this.reports.length > 0) {
        Row() {
          this.SummaryCard('Cost', `¥${formatFen(this.getTotalCost())}`)
          this.SummaryCard('Impr.', formatNumber(this.getTotalImpressions()))
          this.SummaryCard('Clicks', formatNumber(this.getTotalClicks()))
        }
        .width('100%')
        .padding({ left: 20, right: 20, top: 8, bottom: 8 })
        .justifyContent(FlexAlign.SpaceBetween)
      }

      // Report list
      if (this.reports.length === 0) {
        Column() {
          Text('No report data for this date')
            .fontSize(16)
            .fontColor('#999999')
          Text('Select a different date to view reports')
            .fontSize(13)
            .fontColor('#cccccc')
            .margin({ top: 8 })
        }
        .width('100%')
        .layoutWeight(1)
        .justifyContent(FlexAlign.Center)
        .alignItems(HorizontalAlign.Center)
      } else {
        List({ space: 1 }) {
          // Table header
          ListItem() {
            this.TableHeaderRow()
          }

          ForEach(this.reports, (item: ReportMetric) => {
            ListItem() {
              this.ReportRow(item)
            }
          }, (item: ReportMetric) => item.campaignId + item.date)
        }
        .width('100%')
        .layoutWeight(1)
        .padding({ left: 20, right: 20, top: 12 })
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
  SummaryCard(label: string, value: string) {
    Column() {
      Text(label)
        .fontSize(11)
        .fontColor('#888888')
        .margin({ bottom: 4 })
      Text(value)
        .fontSize(16)
        .fontWeight(FontWeight.Bold)
        .fontColor('#1a1a1a')
    }
    .padding({ top: 12, bottom: 12, left: 8, right: 8 })
    .backgroundColor('#ffffff')
    .borderRadius(8)
    .alignItems(HorizontalAlign.Center)
  }

  @Builder
  TableHeaderRow() {
    Row() {
      Text('Campaign')
        .fontSize(12)
        .fontWeight(FontWeight.Medium)
        .fontColor('#888888')
        .layoutWeight(2)
      Text('Cost')
        .fontSize(12)
        .fontWeight(FontWeight.Medium)
        .fontColor('#888888')
        .layoutWeight(1)
      Text('Impr.')
        .fontSize(12)
        .fontWeight(FontWeight.Medium)
        .fontColor('#888888')
        .layoutWeight(1)
      Text('Clicks')
        .fontSize(12)
        .fontWeight(FontWeight.Medium)
        .fontColor('#888888')
        .layoutWeight(1)
    }
    .width('100%')
    .padding({ top: 8, bottom: 8, left: 8, right: 8 })
    .backgroundColor('#fafafa')
  }

  @Builder
  ReportRow(metric: ReportMetric) {
    Row() {
      Text(metric.campaignName)
        .fontSize(13)
        .fontColor('#333333')
        .maxLines(1)
        .textOverflow({ overflow: TextOverflow.Ellipsis })
        .layoutWeight(2)

      Text(`¥${formatFen(metric.cost)}`)
        .fontSize(13)
        .fontColor('#333333')
        .layoutWeight(1)

      Text(formatNumber(metric.impressions))
        .fontSize(13)
        .fontColor('#333333')
        .layoutWeight(1)

      Text(formatNumber(metric.clicks))
        .fontSize(13)
        .fontColor('#333333')
        .layoutWeight(1)
    }
    .width('100%')
    .padding({ top: 12, bottom: 12, left: 8, right: 8 })
    .backgroundColor('#ffffff')
  }

  private getTotalCost(): number {
    let total = 0;
    for (let r of this.reports) {
      total += r.cost;
    }
    return total;
  }

  private getTotalImpressions(): number {
    let total = 0;
    for (let r of this.reports) {
      total += r.impressions;
    }
    return total;
  }

  private getTotalClicks(): number {
    let total = 0;
    for (let r of this.reports) {
      total += r.clicks;
    }
    return total;
  }

  private getTodayStr(): string {
    let d = new Date();
    return `${d.getFullYear()}-${(d.getMonth() + 1).toString().padStart(2, '0')}-${d.getDate().toString().padStart(2, '0')}`;
  }

  private shiftDate(days: number) {
    let parts = this.selectedDate.split('-');
    let d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
    d.setDate(d.getDate() + days);
    this.selectedDate = `${d.getFullYear()}-${(d.getMonth() + 1).toString().padStart(2, '0')}-${d.getDate().toString().padStart(2, '0')}`;
    this.loadReports();
  }

  private loadReports() {
    this.isLoading = true;

    // Simulate data loading — replace with ApiClient.get('/reports?date=' + this.selectedDate)
    setTimeout(() => {
      this.reports = [
        { campaignId: '1', campaignName: 'Summer Sale 2026', date: this.selectedDate, cost: 125000, impressions: 45000, clicks: 1200 },
        { campaignId: '2', campaignName: 'App Install Drive', date: this.selectedDate, cost: 89000, impressions: 32000, clicks: 890 },
        { campaignId: '3', campaignName: 'Brand Awareness Q2', date: this.selectedDate, cost: 0, impressions: 0, clicks: 0 },
        { campaignId: '4', campaignName: 'Retargeting Campaign', date: this.selectedDate, cost: 45000, impressions: 18000, clicks: 520 },
        { campaignId: '5', campaignName: 'Holiday Promo', date: this.selectedDate, cost: 0, impressions: 0, clicks: 0 }
      ];
      this.isLoading = false;
    }, 600);
  }
}
