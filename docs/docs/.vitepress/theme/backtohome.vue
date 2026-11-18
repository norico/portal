<script setup lang="ts">
import { withBase } from 'vitepress'
</script>

<template>
  <div class="retour-accueil">
    <a :href="withBase('/')">← Retour à l'accueil</a>
  </div>
</template>

<style scoped>
.retour-accueil {
  position: sticky;
  bottom: 0;
  margin-top: 24px;
  padding: 16px 0;
  background: var(--vp-sidebar-bg-color);
  border-top: 1px solid var(--vp-c-divider);
}

a {
  font-size: 14px;
  font-weight: 500;
  color: var(--vp-c-text-2);
  text-decoration: none;
}

a:hover {
  color: var(--vp-c-brand-1);
}
</style>