<template>
<form
  class="form-signin"
  method="post"
  @submit.prevent="doSubmit"
>
  <fieldset :disabled="busy">
    <v-text-field
      v-model="email"
      label="Email"
      required
    ></v-text-field>
    <v-text-field
      v-model="password"
      :append-icon="expose_password ? 'mdi-eye' : 'mdi-eye-off'"
      :type="expose_password ? 'text' : 'password'"
      label="Pasword"
      required
      @click:append="expose_password = !expose_password"
    ></v-text-field>
    <v-spacer></v-spacer>
    <v-row
      align="start"
    >
      <v-col v-if="error" class="red--text">
        {{ error }}
      </v-col>
      <v-spacer v-else></v-spacer>
      <v-col cols="auto">
        <v-btn
          depressed
          color="success"
          type="submit"
        >
          Sign in
        </v-btn>
      </v-col>
    </v-row>
  </fieldset>
</form>
</template>

<script>
import axios from "axios"
export default {
  name: 'signin-form',
  data() {
    return {
      busy: false,
      email: null,
      password: null,
      error: null,
      expose_password: false,
    }
  },
  methods: {
    doSubmit() {
      if (this.busy) {
        return
      }

      this.busy = true

      const formData = new FormData()

      formData.append('grant_type', 'password')
      formData.append('client_id', process.env.VUE_APP_OAUTH_CLIENT_ID)
      formData.append('client_secret', process.env.VUE_APP_OAUTH_CLIENT_SECRET)
      formData.append('scope', '')
      formData.append('username', this.email)
      formData.append('password', this.password)

      axios.post(
        'api/v/1/authorise',
        formData
      ).then(response => {
        const accessToken = response.data;
        this.$store.dispatch('setAccessToken', accessToken)

        axios.get('api/v/1/member', { headers: { Authorization: `Bearer ${accessToken.access_token}` } })
          .then(response => {
            this.busy = false
            this.email = null
            this.password = null
            this.error = null

            const user = response.data
            this.$store.dispatch('setUser', user)
          }).catch(() => {
            this.busy = false
          })
      }).catch(() => {
        this.busy = false
        this.error = 'invalid email or password'
      })
    }
  }
}
</script>
