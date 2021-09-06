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
      v-if="!recoveryMode"
      v-model="password"
      :append-icon="exposePassword ? 'mdi-eye' : 'mdi-eye-off'"
      :type="exposePassword ? 'text' : 'password'"
      label="Pasword"
      required
      @click:append="exposePassword = !exposePassword"
    ></v-text-field>
    <v-row justify="space-between">
      <v-col cols="auto">
        <router-link to="/signup">Signup</router-link>
      </v-col>
      <v-col cols="auto">
        <a href="#" @click.prevent="recoveryMode = !recoveryMode">
          <template v-if="recoveryMode">Signin</template><template v-else>Forgot?</template>
        </a>
      </v-col>
    </v-row>
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
          :loading="busy"
        >
          <template v-if="recoveryMode">Recover</template><template v-else>Sign in</template>
        </v-btn>
      </v-col>
    </v-row>
  </fieldset>
</form>
</template>

<script>
import axios from "axios"
import { mapActions } from 'vuex'
export default {
  name: 'signin-form',
  data() {
    return {
      busy: false,
      email: null,
      password: null,
      error: null,
      exposePassword: false,
      recoveryMode: false,
    }
  },
  methods: {
    ...mapActions(['setRefreshingToken']),
    doRecovery(data) {
      this.$store.dispatch('getCSRFToken', '/signup/do-signup').then(resp => {
        if (resp.data.csrf) {
          this.$store.dispatch('doRecovery', {
            path: '/api/v/1/member/passwordRecovery',
            data: data,
            headers: {
              headers: {
                'X-CSRF-TOKEN': resp.data.csrf,
              }
            },
          }).then(resp => {
            this.$store.dispatch('toggleSigninForm', false)
            this.busy = false
            this.$store.dispatch('setShowModal', true)
            this.$store.dispatch('setModalColor', 'primary')
            this.$store.dispatch('setPostbackMessage', resp.data.message)
          }).catch(error => {
            this.busy = false
            this.$store.dispatch('setShowModal', true)
            this.$store.dispatch('setModalColor', 'red')
            this.$store.dispatch(
              'setPostbackMessage',
              error.response && error.response.data ? error.response.data : 'Uknown error'
            )
          })
        }
      })
    },
    doSubmit() {
      if (this.busy) {
        return
      }

      this.busy = true
      this.error = null

      const formData = new FormData()

      if (this.recoveryMode) {
        formData.append('email', this.email)
        this.doRecovery(formData)
        return
      }

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
            this.setRefreshingToken(false)

            this.$nextTick().then(() => {
              if (this.$route.name !== 'MemberCentre') {
                this.$router.push('/member/me')
              }
            })

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
