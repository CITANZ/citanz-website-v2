<template>
  <v-card>
    <form class="modal-content" method="post">
      <stripe-element
        class="field stripe-console"
        ref="stripe"
        type="card"
        :stripeOptions="{locale:'nz', hidePostalCode: true}"
        :elOptions="{style: { base: { padding: '0.5em', fontFamily: 'Roboto, Open Sans, Segoe UI, sans-serif', fontSize: '16px', fontSmoothing: 'antialiased' }, invalid: { iconColor: '#FFC7EE', color: '#FFC7EE', }} }"
        :stripe="stripeKey"
        @change="cdcompleted = $event.complete"
      />
      <v-btn color="primary" depressed type="submit" :loading="is_submitting" :disabled="!cdcompleted" @click.prevent="payByCard">Pay {{ formattedAmount }}</v-btn>
    </form>
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
      show_stripe_modal: false,
      cdcompleted: false,
      client_secret: null,
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
          this.show_stripe_modal = false
          this.is_submitting = false
          this.$emit('stripeTokenGranted', resp.token.id)
        }).catch(console.error)
    }
  }
}
</script>
