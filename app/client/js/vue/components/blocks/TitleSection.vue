<template>
  <section :class="['section title-section', { 'd-sr-only': srOnly }, { 'pb-4': site_data.pagetype == 'ReferralPage' }]">
    <v-container>
      <v-row :align="site_data.pagetype == 'ReferralPage' ? 'end' : null">
        <v-col cols="12" md="7">
          <h1>{{ site_data.title }}</h1>
        </v-col>
        <v-col v-if="site_data.excerpt" cols="12" md="5">
          <div class="typography mt-md-3" v-html="site_data.excerpt"></div>
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
