// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
@Component
export struct MetricCard {
  @Prop label: string = '';
  @Prop value: string = '';
  @Prop trend: string = '';      // e.g. '+12.5%' or '-3.2%'
  @Prop trendUp: boolean = true;

  build() {
    Column() {
      Text(this.label)
        .fontSize(12)
        .fontColor('#888888')
        .margin({ bottom: 8 })

      Text(this.value)
        .fontSize(24)
        .fontWeight(FontWeight.Bold)
        .fontColor('#1a1a1a')
        .margin({ bottom: 4 })

      if (this.trend !== '') {
        Row() {
          Text(this.trendUp ? '▲' : '▼')
            .fontSize(10)
            .fontColor(this.trendUp ? '#52c41a' : '#f5222d')
            .margin({ right: 4 })

          Text(this.trend)
            .fontSize(12)
            .fontColor(this.trendUp ? '#52c41a' : '#f5222d')
        }
        .margin({ top: 2 })
      }
    }
    .width('100%')
    .padding({ top: 16, bottom: 16, left: 16, right: 16 })
    .backgroundColor('#ffffff')
    .borderRadius(8)
    .alignItems(HorizontalAlign.Start)
  }
}
