<template>
  <RouterView v-slot="{ Component }">
    <Transition name="page" mode="out-in">
      <component :is="Component" />
    </Transition>
  </RouterView>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

onMounted(async () => {
  // Revalidar sesión al cargar la app
  if (auth.token && !auth.user) {
    try { await auth.fetchMe() } catch (_) { await auth.logout() }
  }
})
</script>
