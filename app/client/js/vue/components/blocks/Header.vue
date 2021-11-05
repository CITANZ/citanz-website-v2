<template>
  <v-app-bar
    color="white"
    fixed
    :elevate-on-scroll="$route.name !== 'MemberCentre'"
    :elevation="$route.name !== 'MemberCentre' ? undefined : 0"
    app
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
        <v-toolbar-items
          :class="[
            'v-toolbar__menu d-none d-sm-flex',
            {'active': showMenu},
            {'animative': hasMounted}
          ]"
          :style="navbarStyles"
          @mouseleave="resetBarPos"
        >
          <v-btn
            v-for="navitem in site_nav"
            ref="navItems"
            :key="navitem.label"
            :to="navitem.isExternal ? null : navitem.url"
            :href="navitem.isExternal ? navitem.url : null"
            :target="navitem.isExternal ? '_blank' : null"
            text
            plain
            :ripple="false"
            :class="{'is-active': navitem.active}"
            @mouseenter="reportNavItemPos"
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
          id="member-menu-button"
          v-else-if="$route.name !== 'MemberCentre'"
          icon
          to="/member/me"
        >
          <v-icon>mdi-account-circle</v-icon>
        </v-btn>
        <v-btn
          id="member-menu-button"
          :class="{active: memberMenuShown}"
          v-else
          icon
          @click.prevent="setMemberMenuShown(!memberMenuShown)"
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
      barWidth: 0,
      barX: 32,
      barOpacity: 0,
      hasMounted: false,
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
    },
    memberMenuShown() {
      this.showMenu = false
    },
    width() {
      setTimeout(() => {
        this.$nextTick().then(this.resetBarPos)
      }, 300)
    },
  },
  computed: {
    ...mapGetters(['showSigninForm', 'user', 'memberMenuShown', 'width']),
    navbarStyles() {
      return `--navbarIndicatorOpacity: ${this.barOpacity}; --navbarIndicatorXPos: ${this.barX}px; --navbarIndicatorWidth: ${this.barWidth}px;`
    },
  },
  methods: {
    ...mapActions(['toggleSigninForm', 'setMemberMenuShown']),
    currentActiveNavItemPos() {
      const item = this.$refs.navItems.find(o => o.$el.classList.contains('is-active'))
      if (!item) {
        return {
          left: 32,
          width: 0,
          opacity: 0,
        }
      }

      const navItem = item.$el
      const offset = parseInt(window.getComputedStyle(navItem).paddingLeft.replace('px', ''))
      return {
        left: navItem.offsetLeft + offset,
        width: navItem.offsetWidth - offset * 2,
        opacity: 1,
      }
    },
    resetBarPos() {
      const activeItem = this.currentActiveNavItemPos()
      this.barOpacity = activeItem.opacity,
      this.barWidth = activeItem.width
      this.barX = activeItem.left
    },
    reportNavItemPos(e) {
      this.hasMounted = true
      const offset = parseInt(window.getComputedStyle(e.currentTarget).paddingLeft.replace('px', ''))
      this.barX = e.currentTarget.offsetLeft + offset
      this.barWidth = e.currentTarget.offsetWidth - offset * 2
      this.barOpacity = 1
    },
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
