<template>
<v-footer>
  <v-container>
    <v-row>
      <v-col cols="12" sm="6" lg="4">
        <p>
          <router-link to="/" class="d-inline-block">
            <v-img width="155" :src="site_data.siteconfig.footer_logo.url" />
          </router-link>
        </p>
        <div class="typography" v-html="site_data.siteconfig.tagline"></div>
      </v-col>
      <v-col cols="12" sm="6" lg="2">
        <p>
          <v-btn :href="site_data.siteconfig.cta.sponsor_us.url">
            {{ site_data.siteconfig.cta.sponsor_us.title }}
          </v-btn>
        </p>
        <p>
          <v-btn :to="site_data.siteconfig.cta.donate.url">
            {{ site_data.siteconfig.cta.donate.title }}
          </v-btn>
        </p>
      </v-col>
      <v-col cols="6" lg="2">
        <h2>Site Map</h2>
        <ul>
          <li
            v-for="(item, i) in site_data.siteconfig.menu"
            :key="`footer-menuitem-${i}`"
          >
            <router-link :to="item.url">{{ item.title }}</router-link>
          </li>
        </ul>
      </v-col>
      <v-col cols="6" lg="2">
        <h2>Get in touch</h2>
        <p><a :href="`mailto:${site_data.siteconfig.contact.email}`">{{ site_data.siteconfig.contact.email }}</a></p>
        <div class="social-links">
          <v-btn
            v-for="(item, name) in site_data.siteconfig.contact.socials"
            :key="`social-networkd-${name}`"
            fab
            :href="item.url"
            target="_blank"
            x-small
            depressed
          >
            <v-icon small dense>mdi-{{ item.icon }}</v-icon>
          </v-btn>
        </div>
      </v-col>
      <v-col cols="12" sm="6" lg="2">
        <h2>Join our Wechat</h2>
        <p>
          <v-img width="88" :src="site_data.siteconfig.contact.wechat_qr.url" />
        </p>
      </v-col>
    </v-row>
    <p class="copyright">
      Copyright © 2018 - {{ currentYear }} CITANZ, Designed by <a target="_blank" href="https://www.linkedin.com/in/jing-hu/" title="Jing's blog">Jing</a>
      <span class="d-sr-only">, and built &amp; maintained by <a href="https://www.leochen.co.nz" target="_blank">Leo Chen</a></span>
    </p>
  </v-container>
</v-footer>
</template>

<script>
export default {
  name: 'site-footer',
  computed: {
    currentYear() {
      return new Date().getFullYear()
    }
  }
}
</script>
