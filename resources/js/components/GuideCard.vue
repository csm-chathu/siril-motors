<template>
  <div class="flex gap-4 bg-white border border-gray-200 rounded-xl p-5">
    <span class="text-2xl shrink-0 leading-none mt-0.5">{{ icon }}</span>
    <div class="flex-1 min-w-0">
      <p class="font-semibold text-gray-800">{{ title }}</p>
      <p class="text-sm text-gray-500 mt-1 leading-relaxed">{{ body }}</p>
      <router-link v-if="link" :to="link"
        class="inline-flex items-center gap-1 mt-3 text-xs font-medium text-blue-600 hover:underline">
        {{ linkLabel }} →
      </router-link>
    </div>
  </div>
</template>

<script setup>
defineProps({
  icon:      { type: String, default: '📌' },
  title:     { type: String, required: true },
  body:      { type: String, required: true },
  link:      { type: String, default: null },
  linkLabel: { type: String, default: 'Go' },
})
</script>
