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
          <div class="d-none d-sm-block about__from-companies" v-if="sectionWhyWeHere.companies && sectionWhyWeHere.companies.length">
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
          <p class="about-why-we-here-image"><v-img :src="sectionWhyWeHere.image.large" /></p>
          <div class="d-sm-none about__from-companies" v-if="sectionWhyWeHere.companies && sectionWhyWeHere.companies.length">
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
          <v-row class="about-stats">
            <v-col cols="auto">
              <p class="h2">{{ sectionWhyWeHere.num_meetups }}</p>
              <p class="subheading">Meetups</p>
            </v-col>
            <v-col offset-md="1">
              <p class="h2">{{ sectionWhyWeHere.num_attendees }}</p>
              <p class="subheading">Attendees</p>
            </v-col>
          </v-row>
        </v-col>
      </v-row>
    </v-container>
  </section>
  <section class="section connect">
    <v-container ref="connect" :style="`--oval-width: ${ovalWidth}px;`">
      <v-row>
        <v-col cols="12" sm="6" md="5">
          <h2 class="mt-12"><span>{{ sectionConnect.title }}</span></h2>
          <div class="typography" v-html="sectionConnect.content"></div>
        </v-col>
        <v-col cols="12" sm="6" md="5" offset-md="1">
          <iconed-block
            v-for="(point, i) in sectionConnect.points"
            :key="`point-${i}`"
            :blockData="point"
            :class="[{'mt-10 mb-16 mb-sm-25': i < sectionConnect.points.length - 1}]"
          />
        </v-col>
      </v-row>
    </v-container>
  </section>
  <section class="section grow">
    <v-container>
      <v-row>
        <v-col cols="12" sm="6" class="grow-image">
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
            :class="[{'mt-10 mb-18': i < sectionGrow.points.length - 1}, 'pr-md-24']"
          />
        </v-col>
      </v-row>
    </v-container>
  </section>
  <section class="section explore">
    <v-container>
      <v-row>
        <v-col cols="12" md="5">
          <h2><span>{{ sectionExplore.title }}</span></h2>
          <div class="typography mb-16" v-html="sectionExplore.content"></div>
          <iconed-block
            v-for="(point, i) in sectionExplore.points"
            :key="`point-${i}`"
            :blockData="point"
            :class="[{'mt-10 mb-18': i < sectionExplore.points.length - 1}]"
          />
        </v-col>
        <v-col cols="12" sm="6" offset-md="1">
          <v-img
            v-if="sectionExplore.image"
            :src="sectionExplore.image.url"
            :alt="`${sectionExplore.image.title}`"
            :aspect-ratio="sectionExplore.image.width/sectionExplore.image.height"
          />
          <div class="explore__jobs">
            <h3 class="loose-font">Featured Jobs</h3>
            <v-row>
              <v-col cols="12" sm="6"
                v-for="(job, i) in sectionExplore.jobs"
                :key="`job-${i}`"
              >
                <div class="explore__job">
                  <p class="explore__job__logo mb-1">
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
            class="cta-section__cta-item"
            :is="!imageCTA.link.open_in_blank && imageCTA.link.is_internal ? 'router-link' : 'a'"
            :to="!imageCTA.link.open_in_blank && imageCTA.link.is_internal ? imageCTA.link.url : null"
            :href="imageCTA.link.open_in_blank || imageCTA.link.is_internal ? imageCTA.link.url : null"
            :target="imageCTA.link.open_in_blank ? '_blank' : null"
          >
            <div class="cta-section__cta-item__image-holder">
              <v-img
                class="cta-section__cta-item__image"
                :src="imageCTA.image.url"
                :aspect-ratio="imageCTA.image.width / imageCTA.image.height"
                :alt="imageCTA.image.titl"
              />
            </div>
            <div class="cta-section__cta-item__content">
              <p class="subheading">{{ imageCTA.subtitle }}</p>
              <h3>{{ imageCTA.title }}</h3>
              <p><span class="cta-section__cta-item__cta">{{ imageCTA.link.title }}</span></p>
            </div>
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
          class="testimonial"
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
  data() {
    return {
      ovalWidth: 432,
    }
  },
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
  },
  created () {
    window.addEventListener('resize', this.handleWindowResize)
  },
  beforeDestroy () {
    window.removeEventListener('resize', this.handleWindowResize)
  },
  mounted () {
    this.$nextTick().then(() => {
      window.dispatchEvent(new Event('resize'))
    })
  },
  methods: {
    workoutOvalWidth () {
      if (this.$refs.connect) {
        this.ovalWidth = this.$refs.connect.clientHeight
      }
    },
    handleWindowResize () {
      this.workoutOvalWidth()
    },
  },
}
</script>
<style lang="scss">
.homepage {
  .page-hero,
  .section {
    &:not(:last-child) {
      margin-bottom: 120px;
      @media (max-width: 768px) {
        margin-bottom: 60px;
      }
    }
  }

  .section {
    &.title-section {
      margin-bottom: 0;
    }

    &.about {
      .col-12 {
        position: relative;
        &:first-child {
          &::before {
            pointer-events: none;
            display: block;
            background-image: url('https://www.cita.org.nz/_resources/app/client/images/red-dots.svg');
            background-repeat: no-repeat;
            content: '';
            width: 62px;
            height: 54px;
            position: absolute;
            z-index: 0;
            transform: translateX(-100%);
            left: 28px;
          }
        }
      }

      .about-why-we-here-image {
        margin-bottom: 60px;
      }

      .about-stats {
        .h2,
        .subheading {
          font-family: Montserrat, sans-serif !important;
        }

        .h2 {
          font-size: 80px;
          font-weight: bold;
          line-height: normal;
          color: var(--secondary-blue);
          margin-bottom: 0;
        }

        .subheading {
          font-size: 18px;
          font-weight: 500;
          line-height: normal;
          letter-spacing: 0.86px;
          color: var(--descriptive);
          text-transform: uppercase;
        }
      }
    }

    &.connect {
      .container {
        &::before {
          pointer-events: none;
          display: block;
          position: absolute;
          width: var(--oval-width);
          height: 100%;
          left: 5%;
          transform: translateX(-50%);
          background-color: var(--oval-blue);
          border-radius: 50%;
          z-index: 0;
          content: '';
        }
      }
    }

    &.grow {
      .grow-image {
        position: relative;
        @media (min-width: 960px) {
          padding-right: 88px;
        }

        &::before {
          pointer-events: none;
          display: block;
          background-image: url('https://www.cita.org.nz/_resources/app/client/images/blue-dots.svg');
          background-repeat: no-repeat;
          content: '';
          width: 62px;
          height: 54px;
          position: absolute;
          z-index: 0;
          top: -12px;
          right: 0;
          @media (min-width: 960px) {
            transform: translateX(-100%);
          }
        }
      }
    }

    &.explore {
      .explore {
        &__job {
          &s {
            background-color: #f2f8fd;
            padding: 36px 16px 32px;
            h3 {
              font-family: "Source Sans Pro", sans-serif !important;
              color: var(--h3-black);
              font-size: 16px;
              line-height: 1.5;
              letter-spacing: normal;

              &:not(:last-child) {
                margin-bottom: 3rem;
              }
            }
          }

          &__logo {
            img {
              max-height: 24px;
              width: auto;
            }
          }

          &__title {
            color: var(--h3-black);
          }

          &__location {
            color: var(--descriptive);
            line-height: 2;
          }
        }
      }
    }

    &.cta-section {
      .cta-section {
        &__cta-item {
          text-decoration: none;
          display: flex;
          flex-direction: column;
          height: 100%;

          .cta-section__cta-item__content {
            flex: 1;
          }

          &__image {
            &-holder {
              overflow: hidden;
            }
          }

          &__content {
            padding: 30px 24px 48px;
            border: solid 1px #f5f6f6;
            border-top: none;

            .subheading {
              text-transform: uppercase;
              font-family: Montserrat, sans-serif !important;
              font-size: 14px;
              font-weight: 500;
              line-height: 1.86;
              letter-spacing: normal;
              color: var(--descriptive);
              margin-bottom: 10px;
            }

            h3 {
              color: var(--dark);
              font-size: 18px;
              font-weight: 600 !important;
              line-height: 1.78;
              letter-spacing: normal;
              &:not(:last-child) {
                margin-bottom: 22px;
              }
            }
          }

          &,
          &__image,
          &__cta {
            transition: all ease-out .3s;
            transition-delay: 0.15s;
          }

          &:hover,
          &:focus {
            box-shadow: 0 2px 10px 0px rgb(0 0 0 / 20%);
            .cta-section__cta-item__image {
              transform: scale(1.1);
              transition-delay: 0s;
            }

            .cta-section__cta-item__cta {
              color: var(--orange);
            }
          }
        }
      }
    }
  }
}
</style>
