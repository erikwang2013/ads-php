# Admin Page Generator

[中文](docs/skills/admin-page-generator.md) | [English](docs/skills/admin-page-generator.en.md) | [한국어](docs/skills/admin-page-generator.ko.md) | [Русский](docs/skills/admin-page-generator.ru.md) | [Deutsch](docs/skills/admin-page-generator.de.md) | [Français](docs/skills/admin-page-generator.fr.md) | [Español](docs/skills/admin-page-generator.es.md) | [Português](docs/skills/admin-page-generator.pt.md) | [हिन्दी](docs/skills/admin-page-generator.hi.md) | [العربية](docs/skills/admin-page-generator.ar.md) | [বাংলা](docs/skills/admin-page-generator.bn.md) | [Bahasa Indonesia](docs/skills/admin-page-generator.id.md) | [日本語](docs/skills/admin-page-generator.ja.md)

प्रोजेक्ट के स्थापित पैटर्न का पालन करते हुए Vue3 एडमिन पेज जनरेट करें।

## तकनीकी स्टैक
Vue 3 + TypeScript + Element Plus + Pinia + ECharts (vue-echarts) + Axios

## फ़ाइल संरचना
```
admin/public/web/src/
├── api/{module}.ts          # Axios API module
├── views/{module}/          # Page components
├── components/              # Shared components
├── stores/                  # Pinia stores
└── router/index.ts          # Route definitions
```

## API मॉड्यूल टेम्पलेट
```typescript
// admin/public/web/src/api/example.ts
import { api } from './index'

export const exampleApi = {
  list(params?: any) { return api.get('/example', { params }) },
  create(data: any) { return api.post('/example', data) },
  update(id: number, data: any) { return api.put(`/example/${id}`, data) },
  destroy(id: number) { return api.delete(`/example/${id}`) },
}
```

## सूची पेज टेम्पलेट
```vue
<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { exampleApi } from '@/api/example'

const loading = ref(false)
const list = ref<any[]>([])
const pagination = reactive({ page: 1, perPage: 20, total: 0 })

async function fetchList() {
  loading.value = true
  const data = await exampleApi.list({ ...pagination })
  list.value = data.list; pagination.total = data.pagination.total
  loading.value = false
}

async function handleDelete(row: any) {
  await ElMessageBox.confirm('确定删除？', '提示', { type: 'warning' })
  await exampleApi.destroy(row.id); ElMessage.success('已删除'); fetchList()
}

onMounted(fetchList)
</script>
```

## नियम

1. **कॉपीराइट**: हर `.ts`/`.vue` फ़ाइल `Copyright (c) 2026 erik...` से शुरू होती है
2. **API क्लाइंट**: `@/api/index` से पहले से कॉन्फ़िगर किए गए `api` इंस्टेंस का उपयोग करें — यह `ApiResponse<T>` एनवेलप को स्वचालित रूप से अनरैप करता है
3. **पैसा प्रदर्शन**: `@/utils/format` से `formatFen()` का उपयोग करें — सभी बैकएंड मान fen (分) में हैं
4. **प्लेटफ़ॉर्म बैज**: `<PlatformBadge :platform="row.platform" />` कंपोनेंट का उपयोग करें
5. **मेट्रिक कार्ड**: `format='money'|'number'|'percent'` के साथ `<MetricCard>` का उपयोग करें
6. **रूट्स**: `router/index.ts` में `children` ऐरे के अंदर जोड़ें
7. **SideNav**: `components/layout/SideNav.vue` में मेन्यू आइटम जोड़ें

## नया पेज जोड़ना — पूर्ण चेकलिस्ट
1. `api/{module}.ts` बनाएँ
2. `views/{module}/{Page}.vue` बनाएँ
3. `router/index.ts` में रूट जोड़ें
4. `components/layout/SideNav.vue` में मेन्यू आइटम जोड़ें
5. TypeScript सत्यापित करने के लिए `npx vue-tsc --noEmit` चलाएँ
