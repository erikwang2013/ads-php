<template>
  <el-dialog
    v-model="confirmStore.visible"
    :title="confirmStore.options?.title || $t('confirm.title')"
    width="420px"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
  >
    <div class="confirm-body">
      <el-icon class="warn-icon" :size="40" color="#f56c6c"><WarningFilled /></el-icon>
      <p class="confirm-message">{{ confirmStore.options?.message }}</p>
      <p v-if="confirmStore.options?.requireTyping !== false" class="confirm-hint">
        {{ $t('confirm.typeConfirm', { word: confirmStore.options?.confirmWord || 'DELETE' }) }}
      </p>
      <el-input
        v-if="confirmStore.options?.requireTyping !== false"
        v-model="confirmStore.typedWord"
        :placeholder="confirmStore.options?.confirmWord || 'DELETE'"
        @keyup.enter="confirmStore.confirm"
      />
    </div>
    <template #footer>
      <el-button @click="confirmStore.cancel">{{ $t('common.cancel') }}</el-button>
      <el-button
        type="danger"
        :disabled="confirmStore.options?.requireTyping !== false && confirmStore.typedWord !== (confirmStore.options?.confirmWord || 'DELETE')"
        :loading="confirmStore.loading"
        @click="confirmStore.confirm"
      >
        {{ confirmStore.options?.confirmText || $t('common.confirm') }}
      </el-button>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { WarningFilled } from '@element-plus/icons-vue'
import { useConfirmStore } from '@/stores/confirm'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const confirmStore = useConfirmStore()
</script>

<style scoped>
.confirm-body { text-align: center; padding: 10px 0; }
.warn-icon { margin-bottom: 12px; }
.confirm-message { font-size: 15px; color: #303133; margin: 0 0 12px; }
.confirm-hint { font-size: 13px; color: #909399; margin: 0 0 12px; }
.confirm-hint :deep(code) { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-weight: bold; color: #f56c6c; }
</style>
