<template>
<v-footer>
  <v-container>
    <v-row class="mb-0">
      <v-col cols="12" sm="6" lg="4">
        <p class="mb-6">
          <router-link to="/" class="d-inline-block">
            <v-img width="155" :src="site_data.siteconfig.footer_logo.url" />
          </router-link>
        </p>
        <div class="typography" v-html="site_data.siteconfig.tagline"></div>
      </v-col>
      <v-col cols="12" sm="6" lg="2">
        <div class="pt-sm-16 text-lg-right">
          <p>
            <v-btn color="primary" depressed :href="site_data.siteconfig.cta.sponsor_us.url">
              {{ site_data.siteconfig.cta.sponsor_us.title }}
            </v-btn>
          </p>
          <p>
            <v-btn color="primary" depressed :to="site_data.siteconfig.cta.donate.url">
              {{ site_data.siteconfig.cta.donate.title }}
            </v-btn>
          </p>
        </div>
      </v-col>
      <v-col cols="6" lg="2">
        <div class="pt-lg-4 pl-lg-12 footer-links">
          <h2 class="footer-col-title">Site Map</h2>
          <ul>
            <li
              v-for="(item, i) in site_data.siteconfig.menu"
              :key="`footer-menuitem-${i}`"
            >
              <component
                :is="item.isExternal ? 'a' : 'router-link'"
                :to="item.isExternal ? null : item.url"
                :href="item.isExternal ? item.url : null"
                :target="item.isExternal ? '_blank' : null"
              >{{ item.title }}</component>
            </li>
          </ul>
        </div>
      </v-col>
      <v-col cols="6" lg="2">
        <div class="pt-lg-4 footer-links">
          <h2 class="footer-col-title">Get in touch</h2>
          <p><a :href="`mailto:${site_data.siteconfig.contact.email}`">{{ site_data.siteconfig.contact.email }}</a></p>
          <div class="social-links">
            <v-btn
              class="social-link"
              v-for="(item, name, i) in site_data.siteconfig.contact.socials"
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
        </div>
      </v-col>
      <v-col cols="12" sm="6" lg="2">
        <div class="pt-lg-4 pl-lg-8">
          <h2 class="footer-col-title mb-5">Join our Wechat</h2>
          <p class="qr-holder">
            <v-img width="88" :src="site_data.siteconfig.contact.wechat_qr.url" />
          </p>
        </div>
      </v-col>
    </v-row>
    <p class="copyright mb-0">
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
