<template>
  <div class="captcha-widget" v-if="visible">
    <div class="captcha-header">
      <span>{{ $t('captcha.title') }}</span>
      <el-icon class="close-btn" @click="close"><Close /></el-icon>
    </div>

    <div class="captcha-body">
      <!-- Background image with puzzle -->
      <div class="captcha-canvas" ref="canvasRef" @mousedown="startDrag" @mousemove="onDrag" @mouseup="endDrag" @mouseleave="endDrag" @touchstart.prevent="startTouch" @touchmove.prevent="onTouch" @touchend.prevent="endDrag">
        <img :src="bgImage" class="bg-image" alt="" />
        <img :src="pzImage" class="puzzle-image" :style="{ left: puzzleLeft + 'px' }" alt="" />
      </div>

      <!-- Slider track -->
      <div class="slider-track" ref="trackRef">
        <div class="slider-fill" :style="{ width: sliderLeft + 'px' }"></div>
        <div class="slider-btn" :style="{ left: sliderLeft + 'px' }" @mousedown.prevent="startDrag" @touchstart.prevent="startTouch">
          <el-icon><DArrowRight /></el-icon>
        </div>
        <span class="slider-text">{{ sliderLeft > 0 ? '' : $t('captcha.slideHint') }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Close, DArrowRight } from '@element-plus/icons-vue'
import { api } from '@/api/index'

const emit = defineEmits(['verified', 'close'])

const visible = ref(true)
const bgImage = ref('')
const pzImage = ref('')
const token = ref('')
const puzzleLeft = ref(0)
const sliderLeft = ref(0)
const canvasRef = ref<HTMLElement>()
const trackRef = ref<HTMLElement>()
let dragging = false
let startX = 0
let maxLeft = 0

async function fetchCaptcha() {
  const data = await api.get('/captcha/generate')
  bgImage.value = 'data:image/png;base64,' + data.bg_image
  pzImage.value = 'data:image/png;base64,' + data.pz_image
  token.value = data.token
  maxLeft = 250
}

function startDrag(e: MouseEvent) { dragging = true; startX = e.clientX }
function startTouch(e: TouchEvent) { dragging = true; startX = e.touches[0].clientX }

function onDrag(e: MouseEvent) {
  if (!dragging) return
  const delta = e.clientX - startX
  sliderLeft.value = Math.max(0, Math.min(delta, maxLeft))
  puzzleLeft.value = sliderLeft.value
}

function onTouch(e: TouchEvent) {
  if (!dragging) return
  const delta = e.touches[0].clientX - startX
  sliderLeft.value = Math.max(0, Math.min(delta, maxLeft))
  puzzleLeft.value = sliderLeft.value
}

async function endDrag() {
  if (!dragging) return
  dragging = false
  if (sliderLeft.value < 5) return

  try {
    const result = await api.post('/captcha/verify', { token: token.value, offset_x: sliderLeft.value })
    if (result.valid) {
      emit('verified', { token: token.value, offset: sliderLeft.value })
      visible.value = false
    } else {
      sliderLeft.value = 0
      puzzleLeft.value = 0
      fetchCaptcha()
    }
  } catch {
    sliderLeft.value = 0
    puzzleLeft.value = 0
    fetchCaptcha()
  }
}

function close() { visible.value = false; emit('close') }

onMounted(fetchCaptcha)
</script>

<style scoped>
.captcha-widget {
  width: 340px;
  background: #fff;
  border: 1px solid #e4e7ed;
  border-radius: 8px;
  overflow: hidden;
}
.captcha-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 16px;
  border-bottom: 1px solid #f0f0f0;
  font-size: 14px;
  font-weight: 500;
}
.close-btn { cursor: pointer; }
.captcha-body { padding: 16px; }
.captcha-canvas {
  position: relative;
  width: 300px;
  height: 150px;
  margin-bottom: 16px;
  overflow: hidden;
  border-radius: 4px;
  user-select: none;
}
.bg-image { width: 100%; height: 100%; object-fit: cover; }
.puzzle-image {
  position: absolute;
  top: 0;
  box-shadow: 0 0 8px rgba(0,0,0,0.4);
}
.slider-track {
  position: relative;
  height: 36px;
  background: #f5f5f5;
  border-radius: 18px;
  overflow: hidden;
}
.slider-fill {
  height: 100%;
  background: linear-gradient(90deg, #e6f7ff, #91d5ff);
  border-radius: 18px;
  transition: width 0.1s;
}
.slider-btn {
  position: absolute;
  top: 0;
  width: 36px;
  height: 36px;
  background: #fff;
  border: 1px solid #d9d9d9;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: grab;
  box-shadow: 0 2px 4px rgba(0,0,0,0.15);
  transition: left 0.1s;
}
.slider-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: #999;
  font-size: 13px;
  pointer-events: none;
}
</style>
