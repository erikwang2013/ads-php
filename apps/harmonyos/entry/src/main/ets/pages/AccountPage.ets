// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
import router from '@ohos.router';
import { PlatformAccount } from '../model/PlatformAccount';

@Entry
@Component
struct AccountPage {
  @State accounts: PlatformAccount[] = [];
  @State isLoading: boolean = false;

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
      if (this.accounts.length === 0) {
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
      'Facebook': '#1877f2',
      'Google Ads': '#ea4335',
      'TikTok': '#000000'
    };
    return colors[platform] || '#1677ff';
  }

  private loadAccounts() {
    this.isLoading = true;

    // Simulate data loading — replace with ApiClient.get('/accounts')
    setTimeout(() => {
      this.accounts = [
        { id: '1', platform: 'Facebook', name: 'Main FB Ads Account', status: 'active' },
        { id: '2', platform: 'Google Ads', name: 'Erik Google Ads', status: 'active' },
        { id: '3', platform: 'TikTok', name: 'TikTok For Business', status: 'error' }
      ];
      this.isLoading = false;
    }, 600);
  }
}
