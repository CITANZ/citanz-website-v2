<template>
  <v-card
    tag="form"
    class="form-profile"
    method="post"
    @submit.prevent="submit"
    autocomplete="off"
  >
    <v-card-title>
      <h2 class="text-h6">Billing Address</h2>
    </v-card-title>
    <v-card-subtitle>Not email address!</v-card-subtitle>
    <v-card-text class="pb-0">
      <vuetify-google-autocomplete
        class="pt-0"
        :id="bypassChromeAutofillID"
        append-icon="mdi-magnify"
        @placechanged="getAddressData"
        @focus="disableChromeAutofill"
        ref="addressField"
        placeholder=" "
        v-model="address"
      ></vuetify-google-autocomplete>
    </v-card-text>
    <v-card-actions class="justify-end pb-4">
      <v-btn color="primary" type="submit" depressed>Submit</v-btn>
    </v-card-actions>
  </v-card>
</template>

<script>
export default {
  name: 'form-just-address',
  emits: ['submit'],
  data() {
    return {
      address: null,
      suburb: null,
      city: null,
      region: null,
      country: null,
      postcode: null,
      inputError: null,
    }
  },
  computed: {
    bypassChromeAutofillID() {
      return 'billing-' + Date.now()
    },
    postData() {
      return {
        Address: this.address,
        Suburb: this.suburb,
        City: this.city,
        Region: this.region,
        Country: this.country,
        Postcode: this.postcode,
      }
    },
  },
  methods: {
    // fuck Chrome - thanks, but no fanks
    disableChromeAutofill() {
      if (!this.$refs.addressField) return
      const field = this.$refs.addressField.$el.querySelector('[name="' + this.bypassChromeAutofillID + '"]')
      field.setAttribute('autocomplete', 'disable-autocomplete')
      field.removeAttribute('placeholder')
    },
    getAddressData(addressData, placeResultData) {
      this.suburb = placeResultData ? placeResultData.vicinity : null
      this.city = addressData ? addressData.locality : null
      this.region = addressData ? addressData.administrative_area_level_1 : null
      this.country = addressData ? addressData.country : null
      this.postcode = addressData ? addressData.postal_code : null
    },
    submit() {
      this.$emit('submit', this.postData)
    },
    refocus() {
      this.$nextTick().then(() => {
        this.$refs.addressField.$el.querySelector('[name="' + this.bypassChromeAutofillID + '"]').focus()
      })
    },
  }
}
</script>
