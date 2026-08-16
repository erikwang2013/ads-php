// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
import router from '@ohos.router';
import { PlatformAccount } from '../model/PlatformAccount';
import { ApiClient } from '../api/ApiClient';
import { strVal, numVal } from '../utils/FormatUtil';

@Entry
@Component
struct AccountPage {
  @State accounts: PlatformAccount[] = [];
  @State isLoading: boolean = false;
  @State errorMessage: string = '';

  aboutToAppear() {
    this.loadAccounts();
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

        Text('Platform Accounts')
          .fontSize(20)
          .fontWeight(FontWeight.Bold)
          .fontColor('#1a1a1a')
          .margin({ left: 12 })

        Blank()
      }
      .width('100%')
      .padding({ left: 20, right: 20, top: 16, bottom: 16 })
      .backgroundColor('#ffffff')

      // Account list
      if (this.isLoading && this.accounts.length === 0) {
        Column() {
          Text('Loading...')
            .fontSize(16)
            .fontColor('#999999')
        }
        .width('100%')
        .layoutWeight(1)
        .justifyContent(FlexAlign.Center)
        .alignItems(HorizontalAlign.Center)
      } else if (this.errorMessage !== '' && this.accounts.length === 0) {
        Column() {
          Text('Failed to load accounts')
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
              this.loadAccounts();
            })
        }
        .width('100%')
        .layoutWeight(1)
        .justifyContent(FlexAlign.Center)
        .alignItems(HorizontalAlign.Center)
      } else if (this.accounts.length === 0) {
        Column() {
          Text('No accounts connected')
            .fontSize(16)
            .fontColor('#999999')
          Text('Connect your first advertising platform account to get started')
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
          ForEach(this.accounts, (account: PlatformAccount) => {
            ListItem() {
              this.AccountItem(account)
            }
          }, (account: PlatformAccount) => account.id)
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
  AccountItem(account: PlatformAccount) {
    Row() {
      // Platform icon placeholder
      Column() {
        Text(account.platform.charAt(0).toUpperCase())
          .fontSize(20)
          .fontWeight(FontWeight.Bold)
          .fontColor('#ffffff')
      }
      .width(44)
      .height(44)
      .borderRadius(22)
      .backgroundColor(this.getPlatformColor(account.platform))
      .justifyContent(FlexAlign.Center)
      .alignItems(HorizontalAlign.Center)

      // Account info
      Column() {
        Text(account.name)
          .fontSize(16)
          .fontWeight(FontWeight.Medium)
          .fontColor('#1a1a1a')
          .margin({ bottom: 4 })

        Text(account.platform)
          .fontSize(13)
          .fontColor('#888888')
      }
      .alignItems(HorizontalAlign.Start)
      .margin({ left: 12 })
      .layoutWeight(1)

      // Status indicator
      Text(account.status)
        .fontSize(12)
        .fontColor(account.status === 'active' ? '#52c41a' : '#faad14')
        .padding({ left: 10, right: 10, top: 4, bottom: 4 })
        .backgroundColor(account.status === 'active' ? '#f6ffed' : '#fffbe6')
        .borderRadius(12)
    }
    .width('100%')
    .padding(16)
    .backgroundColor('#ffffff')
    .borderRadius(8)
  }

  private getPlatformColor(platform: string): string {
    const colors: Record<string, string> = {
      'facebook': '#1877f2',
      'google': '#ea4335',
      'google ads': '#ea4335',
      'tiktok': '#000000'
    };
    return colors[platform.toLowerCase()] || '#1677ff';
  }

  /**
   * GET /accounts → data.list（分页）→ PlatformAccount[]。
   * service 字段：id/platform/account_name/status（TINYINT：1=active，0=disabled）。
   */
  private async loadAccounts() {
    this.isLoading = true;
    this.errorMessage = '';
    try {
      let res = await ApiClient.get('/accounts') as Record<string, Object>;
      let list = res['list'] as Object[];
      let items: PlatformAccount[] = [];
      if (list !== undefined) {
        for (let item of list) {
          let m = item as Record<string, Object>;
          let a = new PlatformAccount();
          a.id = String(m['id']);
          a.platform = strVal(m['platform']);
          a.name = strVal(m['account_name']);
          let st = m['status'];
          a.status = st === undefined ? 'active' : (numVal(st) === 1 ? 'active' : 'disabled');
          items.push(a);
        }
      }
      this.accounts = items;
      this.isLoading = false;
    } catch (e) {
      this.errorMessage = (e as Error).message;
      this.isLoading = false;
    }
  }
}
