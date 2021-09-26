<template>
  <v-app-bar
    color="white"
    fixed
    elevate-on-scroll
    app
    height="72"
  >
    <div class="container">
      <div class="d-flex align-center">
        <v-toolbar-title>
          <router-link to="/">
            <v-img
              v-if="site_data.siteconfig.logo"
              :aspect-ratio="144/40"
              max-width="144"
              max-height="40"
              :src="site_data.siteconfig.logo.url"
            />
          </router-link>
        </v-toolbar-title>
        <v-spacer></v-spacer>
        <v-toolbar-items :class="['v-toolbar__menu d-none d-sm-flex', {'active': showMenu}]">
          <v-btn
            v-for="navitem in site_nav"
            :key="navitem.label"
            :to="navitem.url"
            text
            plain
            :ripple="false"
          >{{ navitem.label }}</v-btn>
        </v-toolbar-items>
        <v-btn
          v-if="!user"
          class="btn-signin-form-toggler"
          icon
          @click.prevent="handleUserBtnClicked"
        >
          <v-icon>mdi-account-circle</v-icon>
        </v-btn>
        <v-btn
          v-else
          icon
          to="/member/me"
        >
          <v-icon>mdi-account-circle</v-icon>
        </v-btn>
        <v-app-bar-nav-icon class="d-flex d-sm-none" @click.prevent="showMenu = !showMenu"></v-app-bar-nav-icon>
        <signin-form v-if="showSigninForm" />
      </div>
    </div>
  </v-app-bar>
</template>

<script>
import { mapGetters, mapActions } from 'vuex'
import SigninForm from './forms/SigninForm'

export default {
  name: 'site-header',
  components: {
    'signin-form': SigninForm,
  },
  data() {
    return {
      showMenu: false,
    }
  },
  watch: {
    $route() {
      this.showMenu = false
    },
    showSigninForm(nv) {
      if (nv) {
        window.removeEventListener('mousedown', this.mousedownHandler)
        window.addEventListener('mousedown', this.mousedownHandler)
      }
    }
  },
  computed: {
    ...mapGetters(['showSigninForm', 'user']),
  },
  methods: {
    ...mapActions(['toggleSigninForm']),
    handleUserBtnClicked() {
      this.showMenu = false
      this.toggleSigninForm()
    },
    mousedownHandler(e) {
      if (!(e.target.closest('.form-signin') || e.target.closest('.btn-signin-form-toggler'))) {
        window.removeEventListener('mousedown', this.mousedownHandler)
        this.toggleSigninForm(false)
      }
    }
  }
}
</script>
