<template>
<v-app v-if="site_data">
  <site-header />
  <router-view />
</v-app>
</template>

<script>
import Header from './components/blocks/Header'
export default {
  name: 'App',
  components: {
    'site-header': Header,
  },
  watch: {
    $route(to) {
      this.$store.dispatch("getPageData", to.fullPath)
    }
  },
  created() {
    console.log(this.site_data)
  }
}
</script>
