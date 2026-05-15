// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
import router from '@ohos.router';
import promptAction from '@ohos.promptAction';

@Entry
@Component
struct LoginPage {
  @State username: string = '';
  @State password: string = '';
  @State isLoading: boolean = false;

  build() {
    Column() {
      // Header area
      Column() {
        Text('Ads Platform')
          .fontSize(28)
          .fontWeight(FontWeight.Bold)
          .fontColor('#1a1a1a')
          .margin({ bottom: 8 })

        Text('Sign in to manage your advertising campaigns')
          .fontSize(14)
          .fontColor('#888888')
          .textAlign(TextAlign.Center)
      }
      .width('100%')
      .padding({ top: 80, bottom: 40 })
      .alignItems(HorizontalAlign.Center)

      // Login form
      Column() {
        TextInput({ placeholder: 'Username', text: this.username })
          .onChange((value: string) => {
            this.username = value;
          })
          .width('100%')
          .height(48)
          .margin({ bottom: 16 })
          .backgroundColor('#f5f5f5')
          .borderRadius(8)
          .padding({ left: 16 })

        TextInput({ placeholder: 'Password', text: this.password })
          .onChange((value: string) => {
            this.password = value;
          })
          .type(InputType.Password)
          .width('100%')
          .height(48)
          .margin({ bottom: 24 })
          .backgroundColor('#f5f5f5')
          .borderRadius(8)
          .padding({ left: 16 })

        Button('Sign In')
          .width('100%')
          .height(48)
          .fontSize(16)
          .fontWeight(FontWeight.Medium)
          .backgroundColor('#1677ff')
          .borderRadius(8)
          .enabled(!this.isLoading)
          .onClick(() => {
            this.handleLogin();
          })
      }
      .width('100%')
      .padding({ left: 32, right: 32 })

      Blank()

      // Copyright footer
      Text('Copyright (c) 2026 erik — https://erik.xyz')
        .fontSize(12)
        .fontColor('#aaaaaa')
        .padding({ bottom: 24 })
    }
    .width('100%')
    .height('100%')
    .backgroundColor('#ffffff')
  }

  handleLogin() {
    if (this.username.trim() === '' || this.password.trim() === '') {
      promptAction.showToast({ message: 'Please enter username and password', duration: 2000 });
      return;
    }

    this.isLoading = true;

    // Simulate login — replace with actual API call via ApiClient
    setTimeout(() => {
      this.isLoading = false;
      router.pushUrl({ url: 'pages/DashboardPage' });
    }, 1000);
  }
}
