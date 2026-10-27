// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
import router from '@ohos.router';
import { ReportMetric } from '../model/ReportMetric';
import { ApiClient } from '../api/ApiClient';
import { formatFen, formatNumber, todayStr, shiftDateStr, strVal, numVal } from '../utils/FormatUtil';

@Entry
@Component
struct ReportPage {
  @State reports: ReportMetric[] = [];
  @State isLoading: boolean = false;
  @State errorMessage: string = '';
  // 近 7 天窗口：selectedDate 为窗口结束日（默认今天），date_start = selectedDate - 6
  @State selectedDate: string = todayStr();

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

      // Date selector（近 7 天窗口，中间显示日期范围）
      Row() {
        Text('← Prev')
          .fontSize(14)
          .fontColor('#1677ff')
          .onClick(() => {
            this.selectedDate = shiftDateStr(this.selectedDate, -7);
            this.loadReports();
          })

        Text(this.getRangeLabel())
          .fontSize(14)
          .fontWeight(FontWeight.Medium)
          .fontColor('#1a1a1a')
          .margin({ left: 12, right: 12 })
          .maxLines(1)

        Text('Next →')
          .fontSize(14)
          .fontColor('#1677ff')
          .onClick(() => {
            this.selectedDate = shiftDateStr(this.selectedDate, 7);
            this.loadReports();
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
      if (this.isLoading && this.reports.length === 0) {
        Column() {
          Text('Loading...')
            .fontSize(16)
            .fontColor('#999999')
        }
        .width('100%')
        .layoutWeight(1)
        .justifyContent(FlexAlign.Center)
        .alignItems(HorizontalAlign.Center)
      } else if (this.errorMessage !== '' && this.reports.length === 0) {
        Column() {
          Text('Failed to load report data')
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
              this.loadReports();
            })
        }
        .width('100%')
        .layoutWeight(1)
        .justifyContent(FlexAlign.Center)
        .alignItems(HorizontalAlign.Center)
      } else if (this.reports.length === 0) {
        Column() {
          Text('No report data in this range')
            .fontSize(16)
            .fontColor('#999999')
          Text('Select a different date range to view reports')
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
          }, (item: ReportMetric) => item.date !== '' ? item.date : item.campaignId)
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
      Text('Date')
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
      // 按日期维度聚合时显示日期；兼容旧 campaignName 展示
      Text(metric.date !== '' ? metric.date : metric.campaignName)
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

  private getRangeLabel(): string {
    return `${shiftDateStr(this.selectedDate, -6)} ~ ${this.selectedDate}`;
  }

  /**
   * GET /reports/custom（近 7 天，按日期维度聚合）→ data.list → ReportMetric[]。
   * 查询参数与 Flutter report_page.dart 一致：dimensions[]=date & metrics[]=cost,impressions,clicks,conversions。
   */
  private async loadReports() {
    this.isLoading = true;
    this.errorMessage = '';
    try {
      let start = shiftDateStr(this.selectedDate, -6);
      let end = this.selectedDate;
      let path = `/reports/custom?date_start=${start}&date_end=${end}` +
        '&dimensions%5B%5D=date' +
        '&metrics%5B%5D=cost&metrics%5B%5D=impressions&metrics%5B%5D=clicks&metrics%5B%5D=conversions' +
        '&per_page=100';
      let res = await ApiClient.get(path) as Record<string, Object>;
      let list = res['list'] as Object[];
      let items: ReportMetric[] = [];
      if (list !== undefined) {
        for (let item of list) {
          let m = item as Record<string, Object>;
          let r = new ReportMetric();
          r.date = strVal(m['date']);
          r.cost = numVal(m['cost']);
          r.impressions = numVal(m['impressions']);
          r.clicks = numVal(m['clicks']);
          r.conversions = numVal(m['conversions']);
          items.push(r);
        }
      }
      this.reports = items;
      this.isLoading = false;
    } catch (e) {
      this.errorMessage = (e as Error).message;
      this.isLoading = false;
    }
  }
}
