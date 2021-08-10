<template>
<div class="page-content">
  <section class="title-section">
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
  </section>
  <page-hero :hero="site_data.hero" />
  <section class="about">
    <v-container>
      <v-row>
        <v-col cols="12" sm="6">
          <h2>{{ sectionWhyWeHere.title }}</h2>
          <div class="typography" v-html="sectionWhyWeHere.content"></div>
          <div class="d-none d-sm-block about__from-companies">
            <p class="h5">Our members are from</p>
            <p class="about__from-companies__items">
              <span
                v-for="(company, i) in sectionWhyWeHere.companies"
                :key="`company-${i}`"
                class="about__from-companies__item d-inline-block"
                :title="company.title"
              >
                <v-img :src="company.logo" :alt="`${company.title}'s logo`" />
              </span>
            </p>
          </div>
        </v-col>
        <v-col cols="12" sm="6">
          <p><v-img :src="sectionWhyWeHere.image.large" /></p>
          <div class="d-sm-none about__from-companies">
            <p class="h5">Our members are from</p>
            <p class="about__from-companies__items">
              <span
                v-for="(company, i) in sectionWhyWeHere.companies"
                :key="`company-${i}`"
                class="about__from-companies__item d-inline-block"
                :title="company.title"
              >
                <v-img :src="company.logo" :alt="`${company.title}'s logo`" />
              </span>
            </p>
          </div>
          <v-row>
            <v-col cols="auto">
              <p class="h2">{{ sectionWhyWeHere.num_meetups }}</p>
              <p class="subtitle">Meetups</p>
            </v-col>
            <v-col>
              <p class="h2">{{ sectionWhyWeHere.num_attendees }}</p>
              <p class="subtitle">Attendees</p>
            </v-col>
          </v-row>
        </v-col>
      </v-row>
    </v-container>
  </section>
  <section class="connect">
    <v-container>
      <v-row>
        <v-col cols="12" sm="6">
          <h2 class="mt-10"><span>{{ sectionConnect.title }}</span></h2>
          <div class="typography" v-html="sectionConnect.content"></div>
        </v-col>
        <v-col cols="12" sm="6">
          <iconed-block
            v-for="(point, i) in sectionConnect.points"
            :key="`point-${i}`"
            :blockData="point"
            :class="[{'mt-10 mb-sm-25': i == 0}]"
          />
        </v-col>
      </v-row>
    </v-container>
  </section>
</div>
</template>

<script>
import PageHero from '../../blocks/PageHero'
import IconedBlock from '../../blocks/IconedBlock'
export default {
  name: "HomePage",
  components: {
    'iconed-block': IconedBlock,
    'page-hero': PageHero,
  },
  computed: {
    sectionWhyWeHere() {
      return this.site_data.why_we_here
    },
    sectionConnect() {
      return this.site_data.connect
    },
  }
}
</script>
