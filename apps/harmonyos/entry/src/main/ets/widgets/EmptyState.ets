// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
@Component
export struct EmptyState {
  @Prop icon: string = 'O';        // Placeholder icon text
  @Prop title: string = 'No data';
  @Prop description: string = '';
  @Prop actionLabel: string = '';
  @State onAction: () => void = () => {};

  build() {
    Column() {
      // Icon placeholder
      Column() {
        Text(this.icon)
          .fontSize(40)
          .fontColor('#cccccc')
      }
      .width(80)
      .height(80)
      .borderRadius(40)
      .backgroundColor('#f5f5f5')
      .justifyContent(FlexAlign.Center)
      .alignItems(HorizontalAlign.Center)
      .margin({ bottom: 16 })

      Text(this.title)
        .fontSize(16)
        .fontColor('#666666')
        .fontWeight(FontWeight.Medium)
        .margin({ bottom: 8 })

      if (this.description !== '') {
        Text(this.description)
          .fontSize(13)
          .fontColor('#999999')
          .textAlign(TextAlign.Center)
          .maxLines(3)
          .padding({ left: 40, right: 40 })
      }

      if (this.actionLabel !== '' && this.onAction !== undefined) {
        Button(this.actionLabel)
          .fontSize(14)
          .fontColor('#1677ff')
          .backgroundColor('#ffffff')
          .border({ width: 1, color: '#1677ff' })
          .borderRadius(8)
          .margin({ top: 20 })
          .onClick(() => {
            this.onAction();
          })
      }
    }
    .width('100%')
    .padding({ top: 60, bottom: 60 })
    .alignItems(HorizontalAlign.Center)
    .justifyContent(FlexAlign.Center)
  }
}
