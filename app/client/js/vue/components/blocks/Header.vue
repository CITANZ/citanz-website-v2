<template>
  <v-app-bar
    color="white"
    fixed
    elevate-on-scroll
    app
  >
    <v-toolbar-title>
      <router-link to="/">
        <v-img
          v-if="site_data.siteconfig.logo"
          :aspect-ratio="144/40"
          max-width="144"
          max-height="40"
          :src="site_data.siteconfig.logo.url"
        />
      </router-link>
    </v-toolbar-title>
    <v-spacer></v-spacer>
    <v-toolbar-items class="hidden-sm-and-down">
      <v-btn
        v-for="navitem in site_nav"
        :key="navitem.label"
        :to="navitem.url"
        text
        plain
        :ripple="false"
      >{{ navitem.label }}</v-btn>
    </v-toolbar-items>
    <v-app-bar-nav-icon></v-app-bar-nav-icon>
  </v-app-bar>
</template>

<script>
export default {
  name: 'site-header',
}
</script>
