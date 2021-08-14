<template>
<div class="page-content">
  <section-title />
  <page-hero v-if="site_data.hero" :hero="site_data.hero" />
  <v-container>
    <v-row>
      <v-col cols="12" sm="10" md="7" offset-sm="1">
        <article class="typography" v-html="site_data.content"></article>
      </v-col>
    </v-row>
  </v-container>
</div>
</template>

<script>
export default {
    name: "Page"
}
</script>
