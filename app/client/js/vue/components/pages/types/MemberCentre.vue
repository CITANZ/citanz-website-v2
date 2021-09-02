<template>
  <v-main>
    <div class="page-content">
      <section-title :srOnly="true" />
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
            <template v-if="isMe">
              <h2 class="form-title mb-4">Member Profile</h2>
              <p v-if="user.isPaidMember">
                Your membership ends on {{ user.expiry }}
              </p>
              <p v-else>
                <template v-for="subscription in site_data.subscriptions">
                  <v-btn
                    v-for="variant in subscription.variants"
                    :key="`subscription-${variant.id}`"
                    @click.prevent="doSubscription(variant.id)"
                    depressed
                  >{{ variant.variant_title }} - {{ variant.price_label }}</v-btn>
                </template>
              </p>
              <v-divider class="mt-3 mb-4"></v-divider>
              <form-profile :accessToken="access_token" />
              <v-dialog
                v-model="dialog"
                max-width="290"
                :persistent="lockDialog"
              >
                <form-payment
                  ref="paymentForm"
                  @submitting="lockDialog = true"
                  @stripeTokenGranted="submitStripePayment"
                />
              </v-dialog>
            </template>
            <payments-section v-if="isPaymentsSection" />
            <security-section v-if="isSecuritySection" />
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
import PaymentForm from '../../blocks/forms/PaymentForm'
import { mapGetters, mapActions } from 'vuex'
import Payments from './member-pages/Payments'
import Security from './member-pages/Security'

export default {
  name: 'MemberCentre',
  components: {
    'signin-form': SigninForm,
    'form-activation': ActivationForm,
    'form-password': PasswordForm,
    'form-profile': ProfileForm,
    'form-payment': PaymentForm,
    'payments-section': Payments,
    'security-section': Security,
  },
  data() {
    return {
      dialog: false,
      lockDialog: false,
    }
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
    isMe() {
      return this.$route.params.action === 'me' && this.user
    },
    isPaymentsSection() {
      return this.$route.params.action === 'payments'
    },
    isSecuritySection() {
      return this.$route.params.action === 'security'
    },
  },
  methods: {
    ...mapActions(['setAccessToken', 'setUser', 'post', 'setStripeKey']),
    submitStripePayment(token) {
      this.dialog = this.lockDialog = false
      const data = new FormData()
      data.append('token', token)
      this.post({
        path: '/api/v/1/member/payMembership',
        data: data,
        headers: {
          headers: { Authorization: `Bearer ${this.access_token.access_token}` },
        },
      }).then(resp => {

      })
    },
    doSubscription(id) {
      const data = new FormData()
      data.append('id', id)
      this.post({
        path: '/api/v/1/member/prepareMembership',
        data: data,
        headers: {
          headers: { Authorization: `Bearer ${this.access_token.access_token}` },
        },
      }).then(resp => {
        this.setStripeKey(resp.data.stripe_key)
        this.$nextTick().then(() => {
          this.$refs.paymentForm.setAmount(resp.data.amount)
        })
        this.dialog = true
      })
    },
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
<style lang="scss" scoped>
nav {
  .v-list.v-sheet {
    position: sticky;
    top: 76px;
  }
}
</style>
