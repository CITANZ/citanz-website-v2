<template>
<div class="page-hero">
  <v-img
    v-if="isLoneHero"
    :aspect-ratio="firstHero.width/firstHero.height"
    :src="firstHero.large"
  />
</div>
</template>

<script>
export default {
  name: 'page-hero',
  props: {
    hero: Array,
  },
  computed: {
    firstHero() {
      return this.hero[0]
    },
    isLoneHero() {
      return this.hero.length === 1
    }
  }
}
</script>
