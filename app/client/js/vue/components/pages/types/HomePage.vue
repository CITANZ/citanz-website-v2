<template>
<div class="page-content">
  <section-title />
  <page-hero :hero="site_data.hero" />
  <section class="section about">
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
              <p class="subheading">Meetups</p>
            </v-col>
            <v-col>
              <p class="h2">{{ sectionWhyWeHere.num_attendees }}</p>
              <p class="subheading">Attendees</p>
            </v-col>
          </v-row>
        </v-col>
      </v-row>
    </v-container>
  </section>
  <section class="section connect">
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
  <section class="section grow">
    <v-container>
      <v-row>
        <v-col cols="12" sm="6">
          <v-img
            v-if="sectionGrow.image"
            :src="sectionGrow.image.url"
            :alt="`${sectionGrow.image.title}`"
            :aspect-ratio="sectionGrow.image.width/sectionGrow.image.height"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <h2><span>{{ sectionGrow.title }}</span></h2>
          <div class="typography mb-18" v-html="sectionGrow.content"></div>
          <iconed-block
            v-for="(point, i) in sectionGrow.points"
            :key="`point-${i}`"
            :blockData="point"
          />
        </v-col>
      </v-row>
    </v-container>
  </section>
  <section class="section explore">
    <v-container>
      <v-row>
        <v-col cols="12" sm="6">
          <h2><span>{{ sectionExplore.title }}</span></h2>
          <div class="typography" v-html="sectionExplore.content"></div>
          <iconed-block
            v-for="(point, i) in sectionExplore.points"
            :key="`point-${i}`"
            :blockData="point"
            :class="[{'mt-10 mb-sm-25': i == 0}]"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-img
            v-if="sectionExplore.image"
            :src="sectionExplore.image.url"
            :alt="`${sectionExplore.image.title}`"
            :aspect-ratio="sectionExplore.image.width/sectionExplore.image.height"
          />
          <div class="explore__jobs">
            <h3>Featured Jobs</h3>
            <v-row>
              <v-col cols="12" sm="6"
                v-for="(job, i) in sectionExplore.jobs"
                :key="`job-${i}`"
              >
                <div class="explore__job">
                  <p class="explore__job__logo">
                    <img height="24" class="d-block" :src="job.company.mini_logo ? job.company.mini_logo : job.company.logo" :alt="`${job.company.title}'s logo'`" />
                  </p>
                  <p class="explore__job__title mb-0">{{ job.title.trim() }}</p>
                  <p class="explore__job__location mb-0">{{ job.city }} {{ job.country ? `, ${job.country}` : '' }}</p>
                </div>
              </v-col>
            </v-row>
          </div>
        </v-col>
      </v-row>
    </v-container>
  </section>
  <section class="section cta-section">
    <v-container>
      <h2 class="d-sr-only">Feature Events</h2>
      <v-row>
        <v-col
          cols="12"
          sm="6"
          v-for="(imageCTA, i) in sectionCTA"
          :key="`image-cta-${i}`"
        >
          <component
            class="d-block cta-section__cta-item"
            :is="!imageCTA.link.open_in_blank && imageCTA.link.is_internal ? 'router-link' : 'a'"
            :to="!imageCTA.link.open_in_blank && imageCTA.link.is_internal ? imageCTA.link.url : null"
            :href="imageCTA.link.open_in_blank || imageCTA.link.is_internal ? imageCTA.link.url : null"
            :target="imageCTA.link.open_in_blank ? '_blank' : null"
          >
            <v-img
              class="cta-section__cta-item__image"
              :src="imageCTA.image.url"
              :aspect-ratio="imageCTA.image.width / imageCTA.image.height"
              :alt="imageCTA.image.titl"
            />
            <p class="subheading">{{ imageCTA.subtitle }}</p>
            <h3>{{ imageCTA.title }}</h3>
            <p><span class="cta-section__cta-item__cta">{{ imageCTA.link.title }}</span></p>
          </component>
        </v-col>
      </v-row>
    </v-container>
  </section>
  <section class="section testimonials">
    <v-container>
      <h2 class="d-sr-only">Testimonials</h2>
      <v-row>
        <v-col
          cols="12"
          md="4"
          v-for="(testimonial, i) in sectionTestimonial"
          :key="`testimonial-${i}`"
        >
          <div class="typography" v-html="testimonial.content"></div>
          <v-row align="center">
            <v-col cols="auto">
              <v-img :src="testimonial.portrait.url" :width="testimonial.portrait.width" :height="testimonial.portrait.height" />
            </v-col>
            <v-col>
              <h3>{{ testimonial.title }}</h3>
              <p class="mb-0">{{ testimonial.job_title }}</p>
            </v-col>
          </v-row>
        </v-col>
      </v-row>
    </v-container>
  </section>
  <section class="section sponsors">
    <v-container>
      <v-row class="text-center">
        <v-col
          cols="12"
          sm="6"
          offset-sm="3"
        >
            <h2>{{ sectionSponsors.title }}</h2>
            <div class="typography" v-html="sectionSponsors.content"></div>
            <div class="company-logos text-vertical-center">
              <a
                v-for="(sponsor, i) in sectionSponsors.sponsors"
                :key="`sponsor-${i}`"
                :href="sponsor.link.url"
                target="_blank"
                class="company-logo d-inline-block"
              >
                <img class="d-block" :src="sponsor.logoRaw" :height="sponsor.classname == 'catalyst-cloud' ? 33 : null" :alt="`${sponsor.title}'s logo'`" />
              </a>
            </div>
        </v-col>
      </v-row>
    </v-container>
  </section>
  <section class="section upcoming">
    <v-container>
      <v-row>
        <v-col
          cols="12"
          sm="6"
          offset-sm="3"
        >
            <h2 class="text-center">{{ sectionUpcoming.title }}</h2>
            <div class="typography text-center" v-html="sectionUpcoming.content"></div>
        </v-col>
      </v-row>
      <v-row
        class="upcomings text-center"
      >
        <v-col
          v-for="(tile, i) in sectionUpcoming.tiles"
          :key="`upcoming-tile-${i}`"
        >
          <v-img :src="tile.image.url" :aspect-ratio="tile.image.width / tile.image.height" />
          <div class="upcoming-tile__content">
            <h3>{{ tile.title }}</h3>
            <div class="typography" v-html="tile.content"></div>
          </div>
        </v-col>
      </v-row>
    </v-container>
  </section>
</div>
</template>

<script>
import IconedBlock from '../../blocks/IconedBlock'
export default {
  name: "HomePage",
  components: {
    'iconed-block': IconedBlock,
  },
  computed: {
    sectionWhyWeHere() {
      return this.site_data.why_we_here
    },
    sectionConnect() {
      return this.site_data.connect
    },
    sectionGrow() {
      return this.site_data.grow
    },
    sectionExplore() {
      return this.site_data.explore
    },
    sectionCTA() {
      return this.site_data.image_cta
    },
    sectionTestimonial() {
      return this.site_data.testimonials
    },
    sectionSponsors() {
      return this.site_data.sponsors
    },
    sectionUpcoming() {
      return this.site_data.upcoming
    },
  }
}
</script>
