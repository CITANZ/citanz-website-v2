<template>
  <div class="page-content">
    <section-title />
    <v-container>
      <v-row>
        <v-col cols="12" sm="4" md="3" tag="nav">
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
        <v-col cols="12" sm="8" md="9">
          <form-password v-if="isResetPassSection" @on-success="onPasswordReset" />
          <template v-else>
            <signin-form v-if="!user" />
            <form-activation v-if="user && !user.verified" :accessToken="access_token" @activated="onAccountActivated" />
          </template>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script>
import SigninForm from '../../blocks/forms/SigninForm'
import ActivationForm from '../../blocks/forms/ActivationForm'
import PasswordForm from '../../blocks/forms/PasswordForm'
import { mapGetters, mapActions } from 'vuex'

export default {
  name: 'MemberCentre',
  components: {
    'signin-form': SigninForm,
    'form-activation': ActivationForm,
    'form-password': PasswordForm,
  },
  created() {
    if (this.site_data.recoveryMode && this.$route.params.action !== 'reset-password') {
        this.$router.replace({ params: { action: 'reset-password'} })
    }
    console.log(this.$route.params)
  },
  computed: {
    ...mapGetters(['access_token', 'user']),
    isRecoveryMode() {
      if (!this.site_data) {
        return false
      }

      return this.site_data.recoveryMode
    },
    isResetPassSection() {
      return this.$route.params.action === 'reset-password'
    },
  },
  methods: {
    ...mapActions(['setAccessToken', 'setUser']),
    doSignout() {
      if (confirm('You sure?')) {
        this.setAccessToken(null)
        this.setUser(null)
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
