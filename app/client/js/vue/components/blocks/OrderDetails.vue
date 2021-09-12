<template>
  <v-progress-linear
    v-if="!details"
    indeterminate
    rounded
    height="6"
  ></v-progress-linear>
  <v-card v-else class="order-details">
    <v-card-title>
      <h2>Order#{{details.cart.ref}}</h2>
    </v-card-title>
    <v-card-subtitle>{{ details.payment.created }}</v-card-subtitle>
    <v-card-text v-if="details.cart.comment">
      {{ details.cart.comment }}
    </v-card-text>
    <v-card-text>
      <v-row class="mb-2">
        <v-col v-if="details.freight" cols="12" sm="6">
          <h3 class="mb-2">Shipping address</h3>
          <p class="mb-0">{{ `${details.shipping.firstname} ${details.shipping.surname}` }}</p>
          <p class="mb-0" v-if="details.shipping.org">{{ details.shipping.org }}</p>
          <p class="mb-0"><template v-if="details.shipping.apartment">{{ details.shipping.apartment }}, </template>{{ details.shipping.address }}</p>
        </v-col>
        <v-col cols="12" sm="6">
          <h3 class="mb-2">Billing address</h3>
          <p class="mb-0">{{ `${details.billing.firstname} ${details.billing.surname}` }}</p>
          <p class="mb-0" v-if="details.billing.org">{{ details.billing.org }}</p>
          <p class="mb-0"><template v-if="details.billing.apartment">{{ details.billing.apartment }}, </template>{{ details.billing.address }}</p>
        </v-col>
      </v-row>
      <v-divider></v-divider>
      <v-simple-table>
        <template v-slot:default>
          <thead>
            <tr>
              <th class="text-left">
                Item
              </th>
              <th class="text-center">
                Qty
              </th>
              <th class="text-right">
                Total
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="item in details.cart.items"
              :key="`order-item-${item.id}`"
            >
              <td class="text-left">{{ item.title }}</td>
              <td class="text-center">{{ item.quantity }}</td>
              <td class="text-right">{{ formattedAmount(item.price * item.quantity) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr v-if="details.cart.discount">
              <td colspan="2" class="text-right"><small>{{ details.cart.discount.title }} ({{ details.cart.discount.desc }})</small></td>
              <td class="text-right"><small>-{{ formattedAmount(details.cart.discount.amount) }}</small></td>
            </tr>
            <tr>
              <td colspan="3" class="text-right">Grand total<span class="text-h5 ml-4">{{ formattedAmount(details.payment.amount) }}</span></td>
            </tr>
          </tfoot>
        </template>
      </v-simple-table>
    </v-card-text>
  </v-card>
</template>

<script>
import { mapGetters, mapActions } from 'vuex'

export default {
  name: 'order-details',
  props: ['orderID'],
  emits: ['loaded'],
  data() {
    return {
      details: null,
    }
  },
  computed: {
    ...mapGetters(['access_token'])
  },
  watch: {
    orderID() {
      this.getDetails()
    }
  },
  mounted() {
    this.getDetails()
  },
  methods: {
    ...mapActions(['get']),
    formattedAmount(prAmount) {
      const amount = typeof(prAmount) === 'number' ? prAmount : parseFloat(prAmount)
      return '$' + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')
    },
    getDetails() {
      this.details = null
      if (!this.orderID) {
        return
      }

      this.get(
        {
          path: `/api/v/1/member/getOrderDetails?id=${this.orderID}`,
          headers: {
            headers: { Authorization: `Bearer ${this.access_token.access_token}` },
          },
        }
      ).then(resp => {
        this.$emit('loaded')
        this.details = resp.data
      })
    }
  }
}
</script>
