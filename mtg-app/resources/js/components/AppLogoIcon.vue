<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { Import } from 'lucide-vue-next';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img src="../../assets/AppLogo.png" class="icon-size" alt="Description">
</template>

<style>
.icon-size {
  object-fit: cover;
}
</style>