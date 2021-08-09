<template>
<section class="page-content">
  <v-container>
    <v-row>
      <v-col cols="12" sm="6">
        <h1>{{ site_data.title }}</h1>
      </v-col>
      <v-col cols="12" sm="6">
        <div class="typography" v-html="site_data.excerpt"></div>
      </v-col>
    </v-row>
  </v-container>
  <page-hero :hero="site_data.hero" />
  <v-container>
    <v-row>
      <v-col cols="12" sm="6">
        <h2>{{ WhyWeHere.title }}</h2>
        <div class="typography" v-html="WhyWeHere.content"></div>
      </v-col>
      <v-col cols="12" sm="6">
        <p><v-img :src="WhyWeHere.image.large" /></p>
        <v-row>
          <v-col cols="auto">
            <p class="h2">{{ WhyWeHere.num_meetups }}</p>
            <p class="subtitle">Meetups</p>
          </v-col>
          <v-col>
            <p class="h2">{{ WhyWeHere.num_attendees }}</p>
            <p class="subtitle">Attendees</p>
          </v-col>
        </v-row>
      </v-col>
    </v-row>
  </v-container>
</section>
</template>

<script>
import PageHero from '../../blocks/PageHero'
export default {
  name: "HomePage",
  components: {
    'page-hero': PageHero,
  },
  computed: {
    WhyWeHere() {
      return this.site_data.why_we_here
    }
  }
}
</script>
