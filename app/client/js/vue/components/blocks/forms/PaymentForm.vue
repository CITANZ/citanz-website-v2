<template>
  <v-card tag="form" class="form-payment" method="post">
    <v-card-title
      class="px-4 pb-4"
    >
      <v-img
        v-if="site_data.siteconfig.footer_logo"
        :aspect-ratio="144/40"
        max-width="144"
        max-height="40"
        :src="site_data.siteconfig.footer_logo.url"
      />
    </v-card-title>
    <v-card-text
      class="px-4 pb-2"
    >
      <stripe-element
        class="stripe-console py-2 px-4"
        ref="stripe"
        type="card"
        :stripeOptions="{locale:'nz', hidePostalCode: true}"
        :elOptions="{
          style: {
            base: { padding: '0.5em', fontFamily: 'Roboto, Open Sans, Segoe UI, sans-serif', fontSize: '16px', fontSmoothing: 'antialiased' },
            invalid: { iconColor: '#FFC7EE', color: '#FFC7EE', }
          }
        }"
        :stripe="stripeKey"
        @change="cdcompleted = $event.complete"
      />
    </v-card-text>
    <v-card-actions
      class="px-4 pb-4 justify-space-between align-center"
    >
      <span class="ml-4 help">with <a href="https://stripe.com/nz" target="_blank">Stripe</a></span>
      <v-btn color="primary" depressed type="submit" :loading="is_submitting" :disabled="!cdcompleted" @click.prevent="payByCard">Pay {{ formattedAmount }}</v-btn>
    </v-card-actions>
  </v-card>
</template>

<script>
import { StripeElement } from 'vue-stripe-better-elements'
import { mapGetters } from 'vuex'

export default {
  name: 'form-payment',
  components: { StripeElement },
  data() {
    return {
      cdcompleted: false,
      is_submitting: false,
      grand_total: 0,
    }
  },
  emits: ['stripeTokenGranted', 'submitting'],
  computed: {
    ...mapGetters(['stripeKey']),
    formattedAmount() {
      const amount = typeof(this.grand_total) === 'number' ? this.grand_total : parseFloat(this.grand_total)
      return amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')
    }
  },
  methods: {
    dismissSubmitting() {
      this.is_submitting = false
    },
    setAmount(amount) {
      this.grand_total = amount
    },
    payByCard () {
      if (this.is_submitting) return false
      this.is_submitting  =   true
      this.$emit('submitting')

      this.$refs.stripe.elements
        .createToken()
        .then(resp => {
          if (resp.error) {
            alert(resp.error.message)
            location.reload()
            return false
          }
          this.$emit('stripeTokenGranted', resp.token.id)
        }).catch(console.error)
    }
  }
}
</script>
