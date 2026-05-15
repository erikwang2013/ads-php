// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
const PLATFORM_COLORS: Record<string, { text: string; bg: string }> = {
  'facebook':  { text: '#1877f2', bg: '#e7f0fd' },
  'google':    { text: '#ea4335', bg: '#fce8e6' },
  'google ads':{ text: '#ea4335', bg: '#fce8e6' },
  'tiktok':    { text: '#000000', bg: '#f0f0f0' },
  'amazon':    { text: '#ff9900', bg: '#fff6e6' },
  'snapchat':  { text: '#fffc00', bg: '#333333' }
};

@Component
export struct PlatformBadge {
  @Prop platform: string = '';
  @Prop compact: boolean = false;

  build() {
    Text(this.platform)
      .fontSize(this.compact ? 10 : 12)
      .fontColor(this.getTextColor())
      .padding({
        left: this.compact ? 6 : 10,
        right: this.compact ? 6 : 10,
        top: this.compact ? 2 : 4,
        bottom: this.compact ? 2 : 4
      })
      .backgroundColor(this.getBgColor())
      .borderRadius(4)
  }

  private getTextColor(): string {
    let key = this.platform.toLowerCase();
    return PLATFORM_COLORS[key]?.text || '#666666';
  }

  private getBgColor(): string {
    let key = this.platform.toLowerCase();
    return PLATFORM_COLORS[key]?.bg || '#f0f0f0';
  }
}
