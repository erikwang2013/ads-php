<template>
  <div class="report-export-page">
    <el-card>
      <template #header>
        <h2 style="margin: 0;">报表导出</h2>
      </template>

      <el-form :model="form" label-width="100px" :inline="false">
        <!-- Date Range -->
        <el-form-item label="日期范围">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
            style="width: 360px;"
          />
        </el-form-item>

        <!-- Dimensions -->
        <el-form-item label="分析维度">
          <el-select
            v-model="form.dimensions"
            multiple
            placeholder="请选择维度"
            style="width: 360px;"
          >
            <el-option label="平台" value="platform" />
            <el-option label="计划" value="campaign_id" />
            <el-option label="日期" value="date" />
          </el-select>
        </el-form-item>

        <!-- Metrics -->
        <el-form-item label="指标">
          <el-checkbox-group v-model="form.metrics">
            <el-checkbox label="cost">花费</el-checkbox>
            <el-checkbox label="impressions">展示量</el-checkbox>
            <el-checkbox label="clicks">点击量</el-checkbox>
            <el-checkbox label="conversions">转化量</el-checkbox>
            <el-checkbox label="ctr">点击率</el-checkbox>
            <el-checkbox label="cvr">转化率</el-checkbox>
            <el-checkbox label="cpc">点击均价</el-checkbox>
            <el-checkbox label="cpm">千次展示价</el-checkbox>
            <el-checkbox label="roi">ROI</el-checkbox>
          </el-checkbox-group>
        </el-form-item>

        <!-- Platform Filter (optional) -->
        <el-form-item label="平台筛选">
          <el-select
            v-model="form.platform"
            placeholder="全部平台"
            clearable
            style="width: 200px;"
          >
            <el-option label="巨量引擎" value="juliang" />
            <el-option label="腾讯广告" value="tencent" />
            <el-option label="百度营销" value="baidu" />
            <el-option label="Google Ads" value="google" />
            <el-option label="Meta Ads" value="meta" />
          </el-select>
        </el-form-item>

        <!-- Format -->
        <el-form-item label="导出格式">
          <el-radio-group v-model="form.format">
            <el-radio label="csv">CSV</el-radio>
            <el-radio label="excel">Excel (.xls)</el-radio>
            <el-radio label="pdf-dashboard">Dashboard PDF</el-radio>
          </el-radio-group>
        </el-form-item>

        <!-- Action -->
        <el-form-item>
          <el-button type="primary" :loading="exporting" @click="handleExport">
            <el-icon style="margin-right: 4px;"><Download /></el-icon>
            导出
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { ElMessage } from 'element-plus'
import { Download } from '@element-plus/icons-vue'
import { exportApi } from '@/api/export'
import { formatDate } from '@/utils/format'

const dateRange = ref<[string, string]>([
  new Date(Date.now() - 7 * 86400000).toISOString().slice(0, 10),
  new Date().toISOString().slice(0, 10),
])

const form = reactive({
  dimensions: ['platform'] as string[],
  metrics: ['cost', 'impressions', 'clicks'] as string[],
  platform: '' as string,
  format: 'csv' as string,
})

const exporting = ref(false)

const buildParams = () => {
  const params: Record<string, any> = {
    format: form.format,
  }
  if (form.format === 'pdf-dashboard') {
    params.format = 'pdf'
  } else {
    params.dimensions = form.dimensions.join(',')
    params.metrics = form.metrics.join(',')
  }
  if (dateRange.value && dateRange.value.length === 2) {
    params.date_start = dateRange.value[0]
    params.date_end = dateRange.value[1]
  }
  if (form.platform) {
    params.platform = form.platform
  }
  return params
}

const handleExport = async () => {
  if (form.format !== 'pdf-dashboard' && form.metrics.length === 0) {
    ElMessage.warning('请至少选择一个指标')
    return
  }
  if (form.format !== 'pdf-dashboard' && form.dimensions.length === 0) {
    ElMessage.warning('请至少选择一个维度')
    return
  }

  exporting.value = true
  try {
    const params = buildParams()
    const isDashboardPdf = form.format === 'pdf-dashboard'

    const response = isDashboardPdf
      ? await exportApi.exportDashboard(params)
      : await exportApi.exportReport(params)

    const blob = response.data instanceof Blob
      ? response.data
      : new Blob([response.data])

    const ext = isDashboardPdf ? 'pdf' : form.format === 'csv' ? 'csv' : 'xls'
    const filename = isDashboardPdf
      ? `dashboard_${formatDate(new Date())}.${ext}`
      : `report_${formatDate(new Date())}.${ext}`

    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = filename
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)

    ElMessage.success('导出成功')
  } catch (err: any) {
    ElMessage.error('导出失败: ' + (err.message || '未知错误'))
  } finally {
    exporting.value = false
  }
}

</script>

<style scoped>
.report-export-page {
  max-width: 800px;
  margin: 0 auto;
}
</style>
