<template>
<v-app v-if="site_data">
  <v-app-bar
    color="white"
    fixed
    elevate-on-scroll
    app
  >
    <v-app-bar-nav-icon></v-app-bar-nav-icon>
    <v-toolbar-title>Title</v-toolbar-title>
    <v-spacer></v-spacer>
    <v-toolbar-items class="hidden-sm-and-down">
      <router-link v-for="navitem in site_nav" :key="navitem.label" :to="navitem.url">
        {{ navitem.label }}
      </router-link>
    </v-toolbar-items>
  </v-app-bar>
  <router-view />
</v-app>
</template>

<script>
export default {
  name: "App",
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
