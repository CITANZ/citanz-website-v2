<template>
<v-app v-if="site_data">
  <site-header />
  <router-view />
  <mail-chimp v-if="$route.name !== 'MemberCentre'" :mcData="site_data.siteconfig.mailchimp" />
  <site-footer />
  <v-dialog
    v-model="modalShown"
    max-width="320"
  >
    <v-card>
      <v-toolbar
        :color="modalColor"
        dark
        flat
        dense
      >{{ modalColor == 'red' ? 'Error' : 'Message' }}</v-toolbar>
      <v-card-text class="pt-4" v-html="postbackMessage"></v-card-text>
    </v-card>
  </v-dialog>
</v-app>
</template>

<script>
import Header from './components/blocks/Header'
import Mailchimp from './components/blocks/Mailchimp'
import Footer from './components/blocks/Footer'
import { mapGetters, mapActions } from 'vuex'

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
      modalShown: false,
    }
  },
  watch: {
    $route(to) {
      this.$store.dispatch('toggleSigninForm', false)
      if (!this.skipFetchOnce) {
        this.$store.dispatch("getPageData", to.fullPath)
      }
      this.setSkipFetchOnce(false)
    },
    showModal(nv) {
      if (nv) {
        this.modalShown = true
      }
    },
    modalShown(nv) {
      if (!nv) {
        this.$store.dispatch('setShowModal', false)
      }
    },
  },
  created() {
    console.log(this.site_data)
    this.updateAccessToken()
    window.addEventListener('resize', this.handleWindowResize)
    window.addEventListener('focus', this.updateAccessToken)
  },
  mounted() {
    this.setWidth(document.body.offsetWidth)
  },
  computed: {
    ...mapGetters([
        'access_token',
        'showModal',
        'postbackMessage',
        'modalColor',
        'refreshingToken',
        'skipFetchOnce',
    ]),
  },
  methods: {
    ...mapActions(['setRefreshingToken', 'setSkipFetchOnce', 'setWidth']),
    handleWindowResize() {
      this.setWidth(document.body.offsetWidth)
    },
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
        this.setRefreshingToken(true)
        this.timerClearer()
        const formData = new FormData()

        formData.append('grant_type', 'refresh_token')
        formData.append('refresh_token', this.access_token.refresh_token)
        formData.append('client_id', process.env.VUE_APP_OAUTH_CLIENT_ID)
        formData.append('client_secret', process.env.VUE_APP_OAUTH_CLIENT_SECRET)

        this.$store.dispatch('refreshToken', formData).then(response => {
          this.$store.dispatch('setAccessToken', response.data)
          this.pendingTokenUpdater = setTimeout(() => { this.updateAccessToken() }, 30 * 1000)
          this.setRefreshingToken(false)
        }).catch(() => {
          this.$store.dispatch("setAccessToken", null)
          this.$store.dispatch("setUser", null)
          this.setRefreshingToken(false)
        })
      } else {
        this.setRefreshingToken(false)
        console.log((this.access_token.created + this.access_token.expires_in - currentTimestamp) + ' second(s) remaining');
      }
    },
  }
}
</script>
