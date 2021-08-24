<template>
<v-form
  class="form-activation"
  method="post"
  @submit.prevent="doActivate"
  :disabled="busy"
>
  <h2 class="form-title">Account activation</h2>
  <p>Your account has not been verified. Please find the the activation code in the email sent to the email address that you used for creating this account, and activate your account to unlock the other features like membership payments.</p>
  <p>
    <small>
      I did not receive any email.
      <v-btn
        :disabled="cooldown >= 0"
        x-small
        text
        :loading="loadingCooldown"
        @click.prevent="sendAgain"
      >
        Send again
        <template v-if="cooldown >= 0">
          ({{ cooldown }}s)
        </template>
      </v-btn>
    </small>
  </p>
  <v-row align="center">
    <v-col>
      <v-text-field
        v-model="activationCode"
        :error-messages="activationCodeError"
        label="Activation code"
        required
        @input="$v.activationCode.$touch()"
        @blur="$v.activationCode.$touch()"
      ></v-text-field>
    </v-col>
    <v-col cols="auto">
      <v-btn :disabled="busy" :loading="busy" depressed type="submit">
        Activate
      </v-btn>
    </v-col>
  </v-row>
</v-form>
</template>

<script>
import axios from 'axios'
import { validationMixin } from 'vuelidate'
import { required, email } from 'vuelidate/lib/validators'

export default {
  name: 'form-activation',
  mixins: [validationMixin],
  validations: {
    activationCode: { required },
  },
  props: {
    accessToken: Object,
  },
  data() {
    return {
      ticker: null,
      loadingCooldown: false,
      cooldown: -1,
      busy: false,
      activationCode: null,
    }
  },
  computed: {
    activationCodeError () {
      const errors = []
      if (!this.$v.activationCode.$dirty) return errors
      !this.$v.activationCode.required && errors.push('Activation code is required.')
      return errors
    },
  },
  created() {
    if (this.$route.query.code) {
      this.activationCode = this.$route.query.code
    }

    this.cooldown = this.site_data.lastSent

    if (this.cooldown > 0) {
      this.countdown(this.cooldown)
    }
  },
  emits: [ 'activated' ],
  methods: {
    countdown(downFrom) {
      if (this.ticker) {
        clearInterval(this.ticker)
        this.ticker = null
      }

      if (downFrom) {
        this.cooldown = downFrom
      }

      this.ticker = setInterval(() => {
        this.cooldown--
        if (this.cooldown < 0) {
          clearInterval(this.ticker)
          this.ticker = null
        }
      }, 1000);
    },
    sendAgain() {
      if (this.loadingCooldown) {
        return false
      }

      this.loadingCooldown = true

      axios.post(
        '/api/v/1/member/resendActiviationCode',
        {},
        {
          headers: { Authorization: `Bearer ${this.accessToken.access_token}` }
        },
      ).then(resp => {
        this.$store.dispatch('setShowModal', true)
        this.$store.dispatch('setModalColor', 'primary')
        this.$store.dispatch('setPostbackMessage', resp.data.message)
        this.loadingCooldown = false
        this.countdown(60)
      }).catch(error => {
        this.$store.dispatch('setShowModal', true)
        this.$store.dispatch('setModalColor', 'red')
        this.$store.dispatch('setPostbackMessage', error.response && error.response.data ? error.response.data.message : 'Uknown error')
        this.loadingCooldown = false
      })
    },
    doActivate() {
      if (this.busy) {
        return false
      }

      this.busy = true

      const data = new FormData()
      data.append('activationCode', this.activationCode)
      axios.post(
        '/api/v/1/member/doActivate',
        data,
        {
          headers: { Authorization: `Bearer ${this.accessToken.access_token}` }
        },
      ).then(resp => {
        this.$store.dispatch('setShowModal', true)
        this.$store.dispatch('setModalColor', 'primary')
        this.$store.dispatch('setPostbackMessage', resp.data.message)
        this.busy = false
        this.$emit('activated', resp.data.user)
      }).catch(error => {
        this.$store.dispatch('setShowModal', true)
        this.$store.dispatch('setModalColor', 'red')
        this.$store.dispatch('setPostbackMessage', error.response && error.response.data ? error.response.data.message : 'Uknown error')
        this.busy = false
      })
    },
  },
}
</script>
