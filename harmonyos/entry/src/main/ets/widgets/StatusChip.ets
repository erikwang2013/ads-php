// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
const STATUS_STYLES: Record<string, { text: string; bg: string }> = {
  'active':   { text: '#389e0d', bg: '#f6ffed' },
  'enabled':  { text: '#389e0d', bg: '#f6ffed' },
  'paused':   { text: '#d48806', bg: '#fffbe6' },
  'disabled': { text: '#d48806', bg: '#fffbe6' },
  'pending':  { text: '#096dd9', bg: '#e6f7ff' },
  'archived': { text: '#999999', bg: '#f5f5f5' },
  'error':    { text: '#cf1322', bg: '#fff2f0' },
  'deleted':  { text: '#999999', bg: '#f5f5f5' }
};

@Component
export struct StatusChip {
  @Prop status: string = '';
  @Prop compact: boolean = false;

  build() {
    Text(this.status)
      .fontSize(this.compact ? 10 : 12)
      .fontColor(this.getTextColor())
      .padding({
        left: this.compact ? 6 : 10,
        right: this.compact ? 6 : 10,
        top: this.compact ? 2 : 4,
        bottom: this.compact ? 2 : 4
      })
      .backgroundColor(this.getBgColor())
      .borderRadius(12)
  }

  private getTextColor(): string {
    let key = this.status.toLowerCase();
    return STATUS_STYLES[key]?.text || '#666666';
  }

  private getBgColor(): string {
    let key = this.status.toLowerCase();
    return STATUS_STYLES[key]?.bg || '#f5f5f5';
  }
}
