<template>
<div class="page-content">
  <section-title />
  <page-hero v-if="site_data.hero" :hero="site_data.hero" />
  <v-container>
    <v-row>
      <v-col cols="12" sm="10" md="7" offset-sm="1">
        <article class="typography" v-html="site_data.content"></article>
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="12" sm="6" offset-sm="3">
        <form method="post" @submit.prevent="doSubmit" novalidate>
          <fieldset :disabled="busy">
            <v-text-field
              v-model="firstName"
              :error-messages="firstNameError"
              label="First name"
              required
              @input="$v.firstName.$touch()"
              @blur="$v.firstName.$touch()"
            ></v-text-field>
            <v-text-field
              v-model="lastName"
              :error-messages="lastNameError"
              label="Last name"
              required
              @input="$v.lastName.$touch()"
              @blur="$v.lastName.$touch()"
            ></v-text-field>
            <v-text-field
              v-model="email"
              :error-messages="emailErrors"
              label="Email"
              required
              @input="$v.email.$touch()"
              @blur="$v.email.$touch()"
            ></v-text-field>
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
            <v-row>
              <v-col>
                <v-checkbox
                  class="my-0"
                  v-model="agreed"
                  :error-messages="checkboxErrors"
                  required
                  @change="$v.agreed.$touch()"
                  @blur="$v.agreed.$touch()"
                >
                  <span slot="label">I have read and accepted the <a @click.stop="popTnC" href="/terms-and-conditions" target="_blank">T&amp;C</a></span>
                </v-checkbox>
              </v-col>
              <v-col cols="auto">
                <v-btn type="submit"
                  :loading="busy"
                  depressed
                >Signup</v-btn>
              </v-col>
            </v-row>
          </fieldset>
        </form>
      </v-col>
    </v-row>

  </v-container>
</div>
</template>
<script>
import { validationMixin } from 'vuelidate'
import { required, email } from 'vuelidate/lib/validators'
import axios from 'axios'
export default {
  name: 'SignupPage',
  mixins: [validationMixin],
  validations: {
    firstName: { required },
    lastName: { required },
    email: { required, email },
    password: { required },
    agreed: {
      checked (val) {
        return val
      },
    },
  },
  data() {
    return {
      firstName: null,
      lastName: null,
      email: null,
      password: null,
      passVisible: false,
      agreed: false,
      busy: false,
    }
  },
  watch: {
    showModal(nv) {
      if (!nv) {
        this.postbackMessage = null
      }
    }
  },
  computed: {
    firstNameError () {
      const errors = []
      if (!this.$v.firstName.$dirty) return errors
      !this.$v.firstName.required && errors.push('First name is required.')
      return errors
    },
    lastNameError () {
      const errors = []
      if (!this.$v.lastName.$dirty) return errors
      !this.$v.lastName.required && errors.push('Last name is required.')
      return errors
    },
    emailErrors () {
      const errors = []
      if (!this.$v.email.$dirty) return errors
      !this.$v.email.email && errors.push('Must be valid email')
      !this.$v.email.required && errors.push('Email is required')
      return errors
    },
    passwordError () {
      const errors = []
      if (!this.$v.password.$dirty) return errors
      !this.$v.password.required && errors.push('Password is required.')
      return errors
    },
    checkboxErrors () {
      const errors = []
      if (!this.$v.agreed.$dirty) return errors
      !this.$v.agreed.checked && errors.push('You must agree to continue!')
      return errors
    },
  },
  methods: {
    popTnC(e) {
      window.open(e.target.href, '_blank')
    },
    doSubmit() {
      this.$v.$touch()
      if (!this.$v.$invalid) {
        this.busy = true
        const path = `${this.$route.path}/do-signup`
        const data = new FormData()

        if (this.firstName) {
          data.append('firstName', this.firstName)
        }

        if (this.lastName) {
          data.append('lastName', this.lastName)
        }

        if (this.password) {
          data.append('password', this.password)
        }

        if (this.email) {
          data.append('email', this.email)
        }

        if (this.agreed !== null && this.agreed !== undefined) {
          data.append('agreed', this.agreed ? 1 : 0)
        }

        this.$store.dispatch('getCSRFToken', path).then(resp => {
          if (resp.data.csrf) {
            this.$store.dispatch('doSignup', {
              path: path,
              data: data,
              headers: {
                headers: {
                  'X-CSRF-TOKEN': resp.data.csrf,
                }
              },
            }).then(resp => {
              this.$store.dispatch('setShowModal', true)
              this.$store.dispatch('setModalColor', 'primary')
              this.$store.dispatch('setPostbackMessage', resp.data.message)
              this.doSignin()
            }).catch(error => {
              this.busy = false
              this.$store.dispatch('setShowModal', true)
              this.$store.dispatch('setModalColor', 'red')
              this.$store.dispatch(
                'setPostbackMessage',
                error.response && error.response.data ? error.response.data : 'Unknown error'
              )
            })
          }
        })
      }
    },
    doSignin() {
      const formData = new FormData()

      formData.append('grant_type', 'password')
      formData.append('client_id', process.env.VUE_APP_OAUTH_CLIENT_ID)
      formData.append('client_secret', process.env.VUE_APP_OAUTH_CLIENT_SECRET)
      formData.append('scope', '')
      formData.append('username', this.email)
      formData.append('password', this.password)
      console.log(this.email)
      console.log(this.password)
      axios.post(
        'api/v/1/authorise',
        formData
      ).then(response => {
        const accessToken = response.data;
        this.$store.dispatch('setAccessToken', accessToken)

        axios.get('api/v/1/member', { headers: { Authorization: `Bearer ${accessToken.access_token}` } })
          .then(response => {
            this.busy = false
            this.$store.dispatch('setUser', response.data)
            this.$store.dispatch('setRefreshingToken', false)
            console.log('does this part ever work???????')
            this.$router.push('/member/me')
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
