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
    </template>
  </div>
</template>

<script>
import { mapGetters, mapActions } from 'vuex'
import memberMixin from '../../../../mixins/memberMixin'
import PaymentForm from '../../../blocks/forms/PaymentForm'
export default {
  name: 'membership-section',
  components: {
    'form-payment': PaymentForm,
  },
  mixins: [ memberMixin ],
  data() {
    return {
      dialog: false,
      lockDialog: false,
    }
  },
  computed: {
    ...mapGetters(['user'])
  },
  watch: {
    refreshingToken(nv, ov) {
      if (!ov && nv) {
        this.loadSectionData()
      }
    },
  },
  created() {
    this.loadSectionData()
  },
  methods: {
    ...mapActions(['post', 'setStripeKey', 'setUser']),
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
          let amount = resp.data.amount
          if (resp.data.discount) {
            amount -= resp.data.discount.amount
          }

          amount = amount < 0 ? 0 : amount
          if (amount > 0) {
            this.$refs.paymentForm.setAmount(amount)
          } else {
            alert('Something went wrong - the amount to pay cannot be 0')
          }
        })
        this.dialog = true
      })
    },
  }
}
</script>
