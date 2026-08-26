# Admin Page Generator

[中文](docs/skills/admin-page-generator.md) | [English](docs/skills/admin-page-generator.en.md) | [한국어](docs/skills/admin-page-generator.ko.md) | [Русский](docs/skills/admin-page-generator.ru.md) | [Deutsch](docs/skills/admin-page-generator.de.md) | [Français](docs/skills/admin-page-generator.fr.md) | [Español](docs/skills/admin-page-generator.es.md) | [Português](docs/skills/admin-page-generator.pt.md) | [हिन्दी](docs/skills/admin-page-generator.hi.md) | [العربية](docs/skills/admin-page-generator.ar.md) | [বাংলা](docs/skills/admin-page-generator.bn.md) | [Bahasa Indonesia](docs/skills/admin-page-generator.id.md) | [日本語](docs/skills/admin-page-generator.ja.md)

프로젝트의 정립된 패턴에 따라 Vue3 관리자 페이지를 생성합니다.

## 기술 스택
Vue 3 + TypeScript + Element Plus + Pinia + ECharts (vue-echarts) + Axios

## 파일 구조
```
admin/public/web/src/
├── api/{module}.ts          # Axios API 모듈
├── views/{module}/          # 페이지 컴포넌트
├── components/              # 공유 컴포넌트
├── stores/                  # Pinia 스토어
└── router/index.ts          # 라우트 정의
```

## API 모듈 템플릿
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

## 목록 페이지 템플릿
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

## 규칙

1. **Copyright**: 모든 `.ts`/`.vue` 파일은 `Copyright (c) 2026 erik...`로 시작
2. **API 클라이언트**: `@/api/index`의 사전 구성된 `api` 인스턴스 사용 — `ApiResponse<T>` 래퍼를 자동 해제
3. **금액 표시**: `@/utils/format`의 `formatFen()` 사용 — 모든 백엔드 값은 분(分) 단위
4. **플랫폼 배지**: `<PlatformBadge :platform="row.platform" />` 컴포넌트 사용
5. **지표 카드**: `<MetricCard>`를 format='money'|'number'|'percent'로 사용
6. **라우트**: `router/index.ts`의 `children` 배열 안에 추가
7. **SideNav**: `components/layout/SideNav.vue`에 메뉴 항목 추가

## 새 페이지 추가 — 전체 체크리스트
1. `api/{module}.ts` 생성
2. `views/{module}/{Page}.vue` 생성
3. `router/index.ts`에 라우트 추가
4. `components/layout/SideNav.vue`에 메뉴 항목 추가
5. `npx vue-tsc --noEmit` 실행하여 TypeScript 검증
