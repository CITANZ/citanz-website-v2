<template>
<v-app v-if="site_data">
  <site-header />
  <router-view />
  <mail-chimp :mcData="site_data.siteconfig.mailchimp" />
  <site-footer />
</v-app>
</template>

<script>
import Header from './components/blocks/Header'
import Mailchimp from './components/blocks/Mailchimp'
import Footer from './components/blocks/Footer'
export default {
  name: 'App',
  components: {
    'site-header': Header,
    'mail-chimp': Mailchimp,
    'site-footer': Footer,
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
