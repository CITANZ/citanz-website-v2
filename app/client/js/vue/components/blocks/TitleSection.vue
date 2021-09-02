<template>
  <section :class="['section title-section', {'d-sr-only': srOnly}]">
    <v-container>
      <v-row>
        <v-col cols="12" sm="6">
          <h1>{{ site_data.title }}</h1>
        </v-col>
        <v-col v-if="site_data.excerpt" cols="12" sm="6">
          <div class="typography" v-html="site_data.excerpt"></div>
        </v-col>
      </v-row>
    </v-container>
  </section>
</template>

<script>
export default {
  name: 'section-title',
  props: {
    srOnly: Boolean,
  }
}
</script>
