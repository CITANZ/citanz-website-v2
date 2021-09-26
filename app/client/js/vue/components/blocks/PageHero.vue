<template>
<div
  :style="`--lone-hero-padding: ${heroPadding}px;`"
  :class="['page-hero', {'alone': isLoneHero}]"
>
  <v-img
    v-if="isLoneHero"
    :aspect-ratio="firstHero.width/firstHero.height"
    :src="firstHero.url"
  />
  <v-container v-else>
    <v-row>
      <v-col cols="7">
        <v-img
          :aspect-ratio="firstHero.width/firstHero.height"
          :src="firstHero.url"
        />
      </v-col>
      <v-col v-if="secondHero" cols="5">
        <v-img
          :aspect-ratio="secondHero.width/secondHero.height"
          :src="secondHero.url"
        />
      </v-col>
    </v-row>
  </v-container>
</div>
</template>

<script>
import { mapGetters } from 'vuex'
export default {
  name: 'page-hero',
  props: {
    hero: Array,
  },
  data() {
    return {
      heroPadding: 0
    }
  },
  computed: {
    ...mapGetters(['width']),
    firstHero() {
      return this.hero[0]
    },
    secondHero() {
      if (this.hero.length > 1) {
        return this.hero[1]
      }

      return null
    },
    isLoneHero() {
      return this.hero.length === 1
    },
  },
  watch: {
    width(nv) {
      const container = document.querySelector('.container')
      this.heroPadding = nv - container.offsetWidth > 0 ? (nv - container.offsetWidth) * 0.5 + 12 : 0
    },
  },
  mounted() {
    this.$nextTick().then(() => {
      const container = document.querySelector('.container')
      this.heroPadding = this.width - container.offsetWidth > 0 ? (this.width - container.offsetWidth) * 0.5 + 12 : 0
    })
  }
}
</script>
