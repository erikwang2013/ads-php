// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
import router from '@ohos.router';
import { Campaign } from '../model/Campaign';
import { ApiClient } from '../api/ApiClient';
import { formatFen, strVal, numVal } from '../utils/FormatUtil';

@Entry
@Component
struct CampaignListPage {
  @State campaigns: Campaign[] = [];
  @State isLoading: boolean = false;
  @State isRefreshing: boolean = false;
  @State errorMessage: string = '';
  private scroller: Scroller = new Scroller();

  aboutToAppear() {
    this.loadCampaigns();
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

        Text('Campaigns')
          .fontSize(20)
          .fontWeight(FontWeight.Bold)
          .fontColor('#1a1a1a')
          .margin({ left: 12 })

        Blank()
      }
      .width('100%')
      .padding({ left: 20, right: 20, top: 16, bottom: 16 })
      .backgroundColor('#ffffff')

      // Campaign list
      if (this.isLoading && this.campaigns.length === 0) {
        Column() {
          Text('Loading...')
            .fontSize(16)
            .fontColor('#999999')
        }
        .width('100%')
        .layoutWeight(1)
        .justifyContent(FlexAlign.Center)
        .alignItems(HorizontalAlign.Center)
      } else if (this.errorMessage !== '' && this.campaigns.length === 0) {
        Column() {
          Text('Failed to load campaigns')
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
              this.loadCampaigns();
            })
        }
        .width('100%')
        .layoutWeight(1)
        .justifyContent(FlexAlign.Center)
        .alignItems(HorizontalAlign.Center)
      } else if (this.campaigns.length === 0) {
        Column() {
          Text('No campaigns found')
            .fontSize(16)
            .fontColor('#999999')
          Text('Pull down to refresh')
            .fontSize(13)
            .fontColor('#cccccc')
            .margin({ top: 8 })
        }
        .width('100%')
        .layoutWeight(1)
        .justifyContent(FlexAlign.Center)
        .alignItems(HorizontalAlign.Center)
      } else {
        Refresh({ refreshing: $$this.isRefreshing }) {
          List({ space: 12, scroller: this.scroller }) {
            ForEach(this.campaigns, (item: Campaign) => {
              ListItem() {
                this.CampaignItem(item)
              }
            }, (item: Campaign) => item.id)
          }
          .width('100%')
          .padding({ left: 20, right: 20, top: 16, bottom: 16 })
          .layoutWeight(1)
          .divider({
            strokeWidth: 0.5,
            color: '#eeeeee'
          })
        }
        .onRefreshing(() => {
          this.isRefreshing = true;
          this.loadCampaigns();
        })
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
  CampaignItem(campaign: Campaign) {
    Column() {
      Row() {
        Text(campaign.name)
          .fontSize(16)
          .fontWeight(FontWeight.Medium)
          .fontColor('#1a1a1a')
          .maxLines(1)
          .textOverflow({ overflow: TextOverflow.Ellipsis })
          .layoutWeight(1)

        this.StatusChip(campaign.status)
      }
      .width('100%')

      Row() {
        this.PlatformBadge(campaign.platform)

        Blank()

        Text(`Budget: ¥${formatFen(campaign.dailyBudget)}`)
          .fontSize(13)
          .fontColor('#888888')
      }
      .width('100%')
      .margin({ top: 8 })
    }
    .width('100%')
    .padding({ top: 14, bottom: 14, left: 16, right: 16 })
    .backgroundColor('#ffffff')
    .borderRadius(8)
  }

  @Builder
  PlatformBadge(platform: string) {
    Text(platform)
      .fontSize(11)
      .fontColor(this.getPlatformColor(platform))
      .padding({ left: 8, right: 8, top: 3, bottom: 3 })
      .backgroundColor(this.getPlatformBgColor(platform))
      .borderRadius(4)
  }

  @Builder
  StatusChip(status: string) {
    Text(status)
      .fontSize(12)
      .fontColor(this.getStatusColor(status))
      .padding({ left: 10, right: 10, top: 4, bottom: 4 })
      .backgroundColor(this.getStatusBgColor(status))
      .borderRadius(12)
  }

  private getPlatformColor(platform: string): string {
    const colors: Record<string, string> = {
      'facebook': '#1877f2',
      'google': '#ea4335',
      'tiktok': '#000000'
    };
    return colors[platform.toLowerCase()] || '#666666';
  }

  private getPlatformBgColor(platform: string): string {
    const colors: Record<string, string> = {
      'facebook': '#e7f0fd',
      'google': '#fce8e6',
      'tiktok': '#f0f0f0'
    };
    return colors[platform.toLowerCase()] || '#f0f0f0';
  }

  private getStatusColor(status: string): string {
    const colors: Record<string, string> = {
      'active': '#52c41a',
      'enabled': '#52c41a',
      'paused': '#faad14',
      'archived': '#999999',
      'pending': '#1890ff'
    };
    return colors[status.toLowerCase()] || '#666666';
  }

  private getStatusBgColor(status: string): string {
    const colors: Record<string, string> = {
      'active': '#f6ffed',
      'enabled': '#f6ffed',
      'paused': '#fffbe6',
      'archived': '#f5f5f5',
      'pending': '#e6f7ff'
    };
    return colors[status.toLowerCase()] || '#f5f5f5';
  }

  /**
   * GET /campaigns → data.list（分页）→ Campaign[]。
   * service 字段：id/name/platform/status/daily_budget（分为单位）。
   */
  private async loadCampaigns() {
    this.isLoading = true;
    this.errorMessage = '';
    try {
      let res = await ApiClient.get('/campaigns') as Record<string, Object>;
      let list = res['list'] as Object[];
      let items: Campaign[] = [];
      if (list !== undefined) {
        for (let item of list) {
          let m = item as Record<string, Object>;
          let c = new Campaign();
          c.id = String(m['id']);
          c.name = strVal(m['name']);
          c.platform = strVal(m['platform']);
          c.status = strVal(m['status']);
          c.dailyBudget = numVal(m['daily_budget']);
          items.push(c);
        }
      }
      this.campaigns = items;
      this.isLoading = false;
      this.isRefreshing = false;
    } catch (e) {
      this.errorMessage = (e as Error).message;
      this.isLoading = false;
      this.isRefreshing = false;
    }
  }
}
