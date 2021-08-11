<template>
<div class="iconed-block pl-15 position-relative">
  <span class="iconed-block__icon d-block position-absolute">
    <v-img :src="blockData.icon" :alt="`${blockData.title} block's icon'`" />
  </span>
  <h3>
    <span class="d-inline-block">{{ blockData.title }}</span>
    <span class="d-inline-block badge">{{ blockData.badge }}</span>
  </h3>
  <div class="typography" v-html="blockData.content"></div>
</div>
</template>

<script>
export default {
  name: 'iconed-block',
  props: {
    blockData: Object,
  },
}
</script>
