<template>
  <v-main>
    <div class="page-content pb-8">
      <section-title :srOnly="true" />
      <v-container>
        <v-row>
          <v-col id="member-menu" :class="{active: memberMenuShown}" cols="12" sm="4" md="3" tag="nav">
            <v-list v-if="user || isRecoveryMode">
              <v-list-item-group>
                <v-list-item
                  v-for="(item, i) in site_data.memberMenu"
                  :key="`member-menuitem-${i}`"
                  :to="item.url"
                >
                  <v-list-item-icon v-if="item.icon">
                    <v-icon v-text="item.icon"></v-icon>
                  </v-list-item-icon>
                  <v-list-item-content>
                    <v-list-item-title v-text="item.title"></v-list-item-title>
                  </v-list-item-content>
                </v-list-item>
                <v-list-item
                  v-if="!isRecoveryMode"
                  @click.prevent="doSignout"
                >
                  <v-list-item-icon>
                    <v-icon v-text="'mdi-logout'"></v-icon>
                  </v-list-item-icon>
                  <v-list-item-content>
                    <v-list-item-title v-text="'Sign out'"></v-list-item-title>
                  </v-list-item-content>
                </v-list-item>
              </v-list-item-group>
            </v-list>
            <v-list v-else>
              <v-list-item-group>
                <v-list-item
                  to="/member/me"
                >
                  <v-list-item-icon>
                    <v-icon v-text="'mdi-account-circle'"></v-icon>
                  </v-list-item-icon>
                  <v-list-item-content>
                    <v-list-item-title v-text="'Sign in'"></v-list-item-title>
                  </v-list-item-content>
                </v-list-item>
              </v-list-item-group>
            </v-list>
          </v-col>
          <v-col cols="12" sm="8" :lg="isJobsSection ? 9: 7" :xl="isJobsSection ? 9 : 5">
            <form-password v-if="isResetPassSection" @on-success="onPasswordReset" />
            <template v-else>
              <signin-form v-if="!user" />
              <form-activation v-else-if="!user.verified" :accessToken="access_token" @activated="onAccountActivated" />
            </template>
            <template v-if="user && user.verified && isMe">
              <h2 class="form-title mb-4">Member Profile</h2>
              <form-profile :accessToken="access_token" />
            </template>
            <membership-section v-if="isMembershipSection" />
            <payments-section v-if="isPaymentsSection" />
            <security-section v-if="isSecuritySection" />
            <jobs v-if="isJobsSection" />
          </v-col>
        </v-row>
      </v-container>
    </div>
  </v-main>
</template>

<script>
import SigninForm from '../../blocks/forms/SigninForm'
import ActivationForm from '../../blocks/forms/ActivationForm'
import PasswordForm from '../../blocks/forms/PasswordForm'
import ProfileForm from '../../blocks/forms/ProfileForm'
import { mapGetters, mapActions } from 'vuex'
import Payments from './member-pages/Payments'
import Security from './member-pages/Security'
import MemberStatus from './member-pages/MemberStatus'
import Jobs from './member-pages/Jobs'

export default {
  name: 'MemberCentre',
  components: {
    'signin-form': SigninForm,
    'form-activation': ActivationForm,
    'form-password': PasswordForm,
    'form-profile': ProfileForm,
    'payments-section': Payments,
    'security-section': Security,
    'membership-section': MemberStatus,
    'jobs': Jobs,
  },
  data() {
    return {
      showStatus: false,
    }
  },
  created() {
    if (this.site_data.recoveryMode && this.$route.params.action !== 'reset-password') {
        this.$router.replace({ params: { action: 'reset-password'} })
    }
  },
  watch: {
    memberMenuShown(nv) {
      window.removeEventListener('mousedown', this.mousedownHandler)
      if (nv) {
        window.addEventListener('mousedown', this.mousedownHandler)
      }
    },
  },
  computed: {
    ...mapGetters(['access_token', 'user', 'memberMenuShown']),
    isRecoveryMode() {
      if (!this.site_data) {
        return false
      }

      return this.site_data.recoveryMode
    },
    isResetPassSection() {
      return this.$route.params.action === 'reset-password'
    },
    isMe() {
      return this.$route.params.action === 'me'
    },
    isMembershipSection() {
      return this.$route.params.action === 'membership'
    },
    isPaymentsSection() {
      return this.$route.params.action === 'payments'
    },
    isSecuritySection() {
      return this.$route.params.action === 'security'
    },
    isJobsSection() {
      return this.$route.params.action === 'referralopportunities'
    },
  },
  methods: {
    ...mapActions(['setAccessToken', 'setUser', 'setMemberMenuShown']),
    mousedownHandler(e) {
      window.removeEventListener('mousedown', this.mousedownHandler)
      if (!e.target.closest('#member-menu') && !e.target.closest('#member-menu-button')) {
        this.setMemberMenuShown(false)
      }
    },
    doSignout() {
      if (confirm('You sure?')) {
        this.setAccessToken(null)
        this.setUser(null)

        if (this.$route.path != '/member/me') {
          this.$router.replace('/member')
        }

        this.$nextTick().then(() => {
          window.scrollTo({
            top: 0,
            behavior: 'smooth',
          })
        })
      }
    },
    onAccountActivated(payload) {
      console.log(payload)
      this.setUser(payload);
    },
    onPasswordReset(payload) {
      if (payload.redirect) {
        this.$router.replace(payload.redirect)
      }
    }
  }
}
</script>
<style lang="scss" scoped>
nav {
  .v-list.v-sheet {
    position: sticky;
    top: 84px;
  }
}
</style>
