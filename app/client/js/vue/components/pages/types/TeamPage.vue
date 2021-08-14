<template>
<div class="page-content">
  <section-title />
  <page-hero v-if="site_data.hero" :hero="site_data.hero" />
  <section class="section section-feature">
    <v-container>
      <v-row>
        <v-col
          cols="12"
          sm="3"
          offset-sm="1"
        >
          <div class="num-meetups">
            <p class="h2">{{ site_data.home_data.num_meetups }}</p>
            <p class="subheading">Meetups</p>
          </div>
          <div class="num-attendees">
            <p class="h2">{{ site_data.home_data.num_attendees }}</p>
            <p class="subheading">Attendees</p>
          </div>
        </v-col>
        <v-col cols="12" sm="7">
          <div class="typography" v-html="site_data.home_data.content"></div>
          <p class="text-vertical-center">
            <span>Sponsored by</span>
            <a
              v-for="(sponsor, i) in site_data.home_data.sponsors"
              :key="`sponsor-${i}`"
              :href="sponsor.link.url"
              target="_blank"
              class="d-inline-block"
            >
              <img class="d-block" :src="sponsor.logoRaw" :height="sponsor.classname == 'catalyst-cloud' ? 33 : null" :alt="`${sponsor.title}'s logo'`" />
            </a>
          </p>
        </v-col>
      </v-row>
    </v-container>
  </section>
  <section class="section section-our-values">
    <v-container>
      <v-row>
        <v-col
          cols="12"
          sm="11"
          offset-sm="1"
        >
          <h2>{{ site_data.believes.title }}</h2>
        </v-col>
        <v-col
          v-for="(point, i) in site_data.believes.points"
          :key="`item-${i}`"
          cols="12"
          sm="5"
          offset-sm="1"
        >
          <h3>{{ point.title }}</h3>
          <div class="typography" v-html="point.content"></div>
        </v-col>
      </v-row>
    </v-container>
  </section>
  <section class="section section-team">
    <v-container>
      <v-row>
        <v-col cols="12" sm="4">
          <h2>{{ site_data.team.title }}</h2>
        </v-col>
        <v-col cols="12" sm="8">
          <div class="typography" v-html="site_data.team.excerpt"></div>
        </v-col>
      </v-row>
    </v-container>
  </section>
  <section class="section section-tiles">
    <v-container>
      <v-row  class="text-sm-center">
        <v-col
          cols="12"
          sm="6"
          v-for="(tile, i) in site_data.cta_tiles"
          :key="`cta-tile-${i}`"
        >
          <h2>{{ tile.title }}</h2>
          <div class="typography" v-html="tile.content"></div>
          <p class="text-vertical-center" v-if="tile.show_sponsor">
            <span>Sponsored by</span>
            <a
              v-for="(sponsor, i) in site_data.home_data.sponsors"
              :key="`sponsor-${i}`"
              :href="sponsor.link.url"
              target="_blank"
              class="d-inline-block"
            >
              <img class="d-block" :src="sponsor.logoRaw" :height="sponsor.classname == 'catalyst-cloud' ? 33 : null" :alt="`${sponsor.title}'s logo'`" />
            </a>
          </p>
          <p v-if="tile.link">
            <v-btn
              :href="tile.link.url"
            >{{ tile.link.title }}</v-btn>
          </p>
        </v-col>
      </v-row>
    </v-container>
  </section>
</div>
</template>

<script>
export default {
  name: 'team-page',
}
</script>
