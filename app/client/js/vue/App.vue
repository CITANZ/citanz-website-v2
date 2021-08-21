<template>
<v-app v-if="site_data">
  <site-header />
  <router-view />
  <mail-chimp :mcData="site_data.siteconfig.mailchimp" />
  <site-footer />
</v-app>
</template>

<script>
import Header from './components/blocks/Header'
import Mailchimp from './components/blocks/Mailchimp'
import Footer from './components/blocks/Footer'
import { mapActions } from 'vuex'
import { mapGetters } from 'vuex'

export default {
  name: 'App',
  components: {
    'site-header': Header,
    'mail-chimp': Mailchimp,
    'site-footer': Footer,
  },
  data() {
    return {
      pendingTokenUpdater: null,
    }
  },
  watch: {
    $route(to) {
      this.$store.dispatch("getPageData", to.fullPath)
    }
  },
  created() {
    console.log(this.site_data)
    this.updateAccessToken()
    window.addEventListener("focus", this.updateAccessToken)
  },
  computed: {
    ...mapGetters(['access_token']),
  },
  methods: {
    timerClearer() {
      if (this.pendingTokenUpdater) {
        clearTimeout(this.pendingTokenUpdater)
        this.pendingTokenUpdater = null
      }
    },
    updateAccessToken() {
      if (!this.access_token) {
        this.$store.dispatch("setUser", null)
        return false
      }

      const currentTimestamp = Math.floor(Date.now() / 1000);
      const needsRefresh = currentTimestamp >= this.access_token.created + this.access_token.expires_in - 60;

      if (needsRefresh) {
        this.timerClearer()
        const formData = new FormData()

        formData.append('grant_type', 'refresh_token')
        formData.append('refresh_token', this.access_token.refresh_token)
        formData.append('client_id', process.env.VUE_APP_OAUTH_CLIENT_ID)
        formData.append('client_secret', process.env.VUE_APP_OAUTH_CLIENT_SECRET)

        axios.post(
          'api/v1/auth/oauth',
          formData
        ).then(response => {
          this.$store.dispatch('setAccessToken', response.data)
          this.pendingTokenUpdater = setTimeout(() => { this.updateAccessToken() }, 30 * 1000)
        }).catch(() => {
          this.pendingTokenUpdater = setTimeout(() => { this.updateAccessToken() }, 30 * 1000)
        })
      } else {
        console.log((this.access_token.created + this.access_token.expires_in - currentTimestamp) + ' second(s) remaining');
      }
    },
  }
}
</script>
