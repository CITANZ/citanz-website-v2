<template>
  <div class="member-status" v-if="showStatus">
    <p>
      <strong>CITANZ MEMBERSHIP</strong>
      <template v-if="user.usedToBeAMember || user.isPaidMember">: {{ user.citaID }}</template>
      <template v-else>- feel like to join?</template>
    </p>
    <p class="text-vertical-center" v-if="user.isPaidMember">
      <template v-if="user.neverExpire">You are a perpetual member. No renewal is required.</template>
      <span v-else>Your membership ends on {{ user.expiry }}.</span>
    </p>
    <p v-else-if="user.usedToBeAMember">
      <span>Your membership has <strong>expired</strong> on <u>{{ user.expiry }}</u>.</span>
    </p>
    <p v-if="user.canRenew || !user.isPaidMember">
      <template v-for="subscription in site_data.subscriptions">
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
      max-width="290"
      :persistent="lockDialog"
    >
      <form-payment
        ref="paymentForm"
        @submitting="lockDialog = true"
        @stripeTokenGranted="submitStripePayment"
      />
    </v-dialog>
  </div>
</template>

<script>
import { mapGetters, mapActions } from 'vuex'
import PaymentForm from '../../../blocks/forms/PaymentForm'
export default {
  name: 'member-status',
  components: {
    'form-payment': PaymentForm,
  },
  props: {
    showStatus: Boolean,
  },
  data() {
    return {
      dialog: false,
      lockDialog: false,
    }
  },
  computed: {
    ...mapGetters(['user', 'access_token'])
  },
  methods: {
    ...mapActions(['post', 'setStripeKey']),
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
  }
}
</script>
