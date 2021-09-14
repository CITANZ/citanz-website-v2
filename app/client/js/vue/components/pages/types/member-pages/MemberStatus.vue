<template>
  <div class="membership-section">
    <h2 class="mb-4">CITANZ Membership</h2>
    <v-progress-linear
      v-if="!sectionDataLoaded"
      indeterminate
      rounded
      height="6"
    ></v-progress-linear>
    <template v-else>
      <p>
        <template v-if="user.usedToBeAMember || user.isPaidMember"><strong>My member ID</strong>: {{ user.citaID }}</template>
        <template v-else>Feel like to join?</template>
      </p>
      <p class="text-vertical-center" v-if="user.isPaidMember">
        <template v-if="user.neverExpire">You are a perpetual member. No renewal is required.</template>
        <span v-else>Your membership ends on {{ user.expiry }}.</span>
      </p>
      <p v-else-if="user.usedToBeAMember">
        <span>Your membership has <strong>expired</strong> on <u>{{ user.expiry }}</u>.</span>
      </p>
      <p v-if="user.canRenew || !user.isPaidMember">
        <template v-for="subscription in sectionData.subscriptions">
          <v-btn
            v-for="variant in subscription.variants"
            :key="`subscription-${variant.id}`"
            @click.prevent="doSubscription(variant.id)"
            depressed
          >{{ variant.variant_title }} - {{ variant.price_label }}</v-btn>
        </template>
      </p>
      <v-dialog
        v-model="dialog"
        max-width="360"
        :persistent="lockDialog"
      >
        <form-payment
          ref="paymentForm"
          @submitting="lockDialog = true"
          @stripeTokenGranted="submitStripePayment"
        />
      </v-dialog>
      <v-dialog
        v-if="missingAddress"
        v-model="showAddressForm"
        max-width="360"
      >
        <form-just-address
          ref="justAddressForm"
          @submit="addressSubmitted"
          @stripeTokenGranted="submitStripePayment"
        />
      </v-dialog>
    </template>
  </div>
</template>

<script>
import { mapGetters, mapActions } from 'vuex'
import memberMixin from '../../../../mixins/memberMixin'
import PaymentForm from '../../../blocks/forms/PaymentForm'
import JustAddressForm from '../../../blocks/forms/JustAddressForm'
export default {
  name: 'membership-section',
  components: {
    'form-payment': PaymentForm,
    'form-just-address': JustAddressForm,
  },
  mixins: [ memberMixin ],
  data() {
    return {
      dialog: false,
      lockDialog: false,
      showAddressForm: false,
      addressObj: null,
      pendingVariantID: null,
    }
  },
  computed: {
    ...mapGetters(['user']),
    missingAddress() {
      if (this.user) {
        return this.user.addressMissing && !this.addressObj
      }

      return true
    },
  },
  watch: {
    showAddressForm(nv) {
      if (nv) {
        window.addEventListener('keydown', this.keydownHandler)
      } else {
        window.removeEventListener('keydown', this.keydownHandler)
      }
    },
    refreshingToken(nv, ov) {
      if (!ov && nv) {
        this.loadSectionData()
      }
    },
  },
  created() {
    window.removeEventListener('keydown', this.keydownHandler)
    this.loadSectionData()
  },
  methods: {
    ...mapActions(['post', 'setStripeKey', 'setUser']),
    keydownHandler(e) {
      if (e.key == 'Enter') {
        e.preventDefault()
        e.stopPropagation()
        e.stopImmediatePropagation()
        return false
      }
    },
    addressSubmitted(address) {
      this.addressObj = address
      this.showAddressForm = false
      this.doSubscription(this.pendingVariantID)
    },
    loadSectionData() {
      if (this.refreshingToken || !this.accessToken) {
        return
      }

      this.get(
        {
          path: `/api/v/1/member/getMembershipData`,
          headers: {
            headers: { Authorization: `Bearer ${this.accessToken.access_token}` },
          },
        }
      ).then(resp => {
        this.sectionDataLoaded = true
        this.setUser(resp.data.user)
        this.sectionData = resp.data
      })
    },
    submitStripePayment(token) {
      const data = new FormData()
      data.append('token', token)
      this.post({
        path: '/api/v/1/member/payMembership',
        data: data,
        headers: {
          headers: { Authorization: `Bearer ${this.access_token.access_token}` },
        },
      }).then(resp => {
        this.$refs.paymentForm.dismissSubmitting()
        this.dialog = this.lockDialog = false

        this.$store.dispatch('setShowModal', true)
        this.$store.dispatch('setModalColor', 'primary')
        this.$store.dispatch(
          'setPostbackMessage',
          '<p>Your payment has gone through. You can view your payment history at <br /><strong>Member centre</strong> > <strong>Payments</strong></p>'
        )

        this.sectionDataLoaded = false
        this.loadSectionData()
      }).catch(error => {
        this.$refs.paymentForm.dismissSubmitting()
        this.dialog = this.lockDialog = false
        this.$store.dispatch('setShowModal', true)
        this.$store.dispatch('setModalColor', 'red')
        this.$store.dispatch('setPostbackMessage', error.response && error.response.data ? error.response.data.message : 'Uknown error')
        this.busy = false
      })
    },
    doSubscription(id) {
      if (this.missingAddress) {
        this.pendingVariantID = id
        this.showAddressForm = true
        setTimeout(() => {
          this.$refs.justAddressForm.refocus()
        }, 100)
        return
      }

      const data = new FormData()
      data.append('id', id)

      if (this.addressObj) {
        data.append('address', JSON.stringify(this.addressObj))
      }

      this.pendingVariantID = this.addressObj = null

      this.post({
        path: '/api/v/1/member/prepareMembership',
        data: data,
        headers: {
          headers: { Authorization: `Bearer ${this.access_token.access_token}` },
        },
      }).then(resp => {
        this.setStripeKey(resp.data.stripe_key)
        this.$nextTick().then(() => {
          const amount = resp.data.payable_total < 0 ? 0 : resp.data.payable_total
          if (amount > 0) {
            this.$refs.paymentForm.setAmount(amount)
          } else {
            alert('Something went wrong - the amount to pay cannot be 0')
          }
        })
        this.dialog = true
      }).catch(error => {
        this.$store.dispatch('setShowModal', true)
        this.$store.dispatch('setModalColor', 'red')
        this.$store.dispatch('setPostbackMessage', error.response && error.response.data ? error.response.data.message : 'Uknown error')
      })
    },
  }
}
</script>
