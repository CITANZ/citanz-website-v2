<template>
<v-form
  class="form-activation"
  method="post"
  @submit.prevent="doActivate"
  :disabled="busy"
>
  <h2 class="form-title">Reset your password</h2>
  <p>Please enter your new password below:</p>
  <v-row align="center">
    <v-col>
      <v-text-field
        v-model="password"
        :append-icon="passVisible ? 'mdi-eye' : 'mdi-eye-off'"
        :type="passVisible ? 'text' : 'password'"
        :error-messages="passwordError"
        label="Password"
        required
        @input="$v.password.$touch()"
        @blur="$v.password.$touch()"
        @click:append="passVisible = !passVisible"
      ></v-text-field>
    </v-col>
    <v-col cols="auto">
      <v-btn :disabled="busy" :loading="busy" depressed type="submit">
        Submit
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
  name: 'form-password',
  mixins: [validationMixin],
  validations: {
    password: { required },
  },
  props: {
    accessToken: Object,
  },
  data() {
    return {
      passVisible: false,
      busy: false,
      password: null,
    }
  },
  computed: {
    passwordError () {
      const errors = []
      if (!this.$v.password.$dirty) return errors
      !this.$v.password.required && errors.push('Password is required.')
      return errors
    },
  },
  emits: [ 'on-success' ],
  methods: {
    doActivate() {
      if (this.busy) {
        return false
      }

      this.busy = true

      const data = new FormData()
      data.append('password', this.password)
      axios.post(
        '/api/v/1/member/setPassword',
        data
      ).then(resp => {
        this.$store.dispatch('setShowModal', true)
        this.$store.dispatch('setModalColor', 'primary')
        this.$store.dispatch('setPostbackMessage', resp.data.message)
        this.busy = false
        this.$emit('on-success', resp.data)
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
